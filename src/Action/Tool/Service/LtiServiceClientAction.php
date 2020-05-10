<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Action\Tool\Service;

use App\Form\Tool\Service\LtiServiceClientType;
use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\MessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Service\Server\Grant\ClientAssertionCredentialsGrant;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class LtiServiceClientAction
{
    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var ClientInterface */
    private $client;

    /** @var Builder */
    private $builder;

    public function __construct(
        Environment $twig,
        FormFactoryInterface $factory,
        ClientInterface $client,
        Builder $builder
    ) {
        $this->twig = $twig;
        $this->factory = $factory;
        $this->client = $client;
        $this->builder = $builder;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->factory->create(LtiServiceClientType::class);

        $form->handleRequest($request);

        $accessTokenOk = true;
        $accessTokenUrl = null;
        $accessTokenData = null;

        $serviceOk = true;
        $serviceUrl = null;
        $serviceData = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            /** @var RegistrationInterface $registration */
            $registration = $formData['registration'];

            try {
                $accessTokenUrl = $registration->getPlatform()->getOAuth2AccessTokenUrl();

                $response = $this->client->request('POST', $accessTokenUrl, [
                    'form_params' => [
                        'grant_type' => ClientAssertionCredentialsGrant::GRANT_TYPE,
                        'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                        'client_assertion' => $this->generateCredentials($registration),
                        'scope' => ''
                    ]
                ]);

                $accessTokenData = json_decode((string)$response->getBody(), true);
            } catch (RequestException $exception) {
                $accessTokenOk = false;
                $accessTokenData = 'Error during access token response';
            }

            if ($accessTokenData['access_token']) {
                try {
                    $serviceUrl = $formData['service_url'] ?? null;

                    if ($serviceUrl) {
                        $serviceResponse = $this->client->request('GET', $serviceUrl, [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $accessTokenData['access_token'],
                            ]
                        ]);

                        $serviceData = json_decode((string)$serviceResponse->getBody(), true);
                    }
                } catch (RequestException $exception) {
                    $serviceOk = false;
                    $serviceData = 'Error during service response';
                }
            } else {
                $serviceOk = false;
                $serviceData = 'Error during service response';
            }
        }

        return new Response(
            $this->twig->render('tool/service/ltiServiceClient.html.twig', [
                'form' => $form->createView(),
                'accessTokenOk' => $accessTokenOk,
                'accessTokenUrl' => $accessTokenUrl,
                'accessTokenData' => $accessTokenData,
                'serviceOk' => $serviceOk,
                'serviceUrl' => $serviceUrl,
                'serviceData' => $serviceData,
            ])
        );
    }

    private function generateCredentials(RegistrationInterface $registration): string
    {
        if (null === $registration->getToolKeyChain()) {
            throw new LtiException('Tool key chain is not configured');
        }

        $now = Carbon::now();

        return $this->builder
            ->withHeader(MessageInterface::HEADER_KID, $registration->getToolKeyChain()->getIdentifier())
            ->identifiedBy(sprintf('%s-%s', $registration->getIdentifier(), $now->getPreciseTimestamp()))
            ->issuedBy($registration->getTool()->getAudience())
            ->relatedTo($registration->getClientId())
            ->permittedFor($registration->getPlatform()->getOAuth2AccessTokenUrl())
            ->issuedAt($now->getTimestamp())
            ->expiresAt($now->addSeconds(MessageInterface::TTL)->getTimestamp())
            ->getToken(new Sha256(), $registration->getToolKeyChain()->getPrivateKey())
            ->__toString();
    }
}
