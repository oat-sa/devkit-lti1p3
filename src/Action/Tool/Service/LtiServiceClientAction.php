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

use App\Form\Generator\FormShareUrlGenerator;
use App\Form\Tool\Service\LtiServiceClientType;
use GuzzleHttp\Exception\RequestException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Service\Client\LtiServiceClientInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Twig\Environment;

class LtiServiceClientAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var FormShareUrlGenerator */
    private $generator;

    /** @var LtiServiceClientInterface */
    private $client;

    public function __construct(
        FlashBagInterface $flashBag,
        Environment $twig,
        FormFactoryInterface $factory,
        FormShareUrlGenerator $generator,
        LtiServiceClientInterface $client
    ) {
        $this->flashBag = $flashBag;
        $this->twig = $twig;
        $this->factory = $factory;
        $this->generator = $generator;
        $this->client = $client;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->factory->create(LtiServiceClientType::class);

        $form->handleRequest($request);

        $serviceData = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            /** @var RegistrationInterface $registration */
            $registration = $formData['registration'];
            $serviceUrl = $formData['service_url'] ?? null;
            $method = $formData['method'] ?? 'GET';
            $media = $formData['media'] ?? null;
            $body = $formData['body'] ?? null;
            $scopes = explode(' ', $formData['scope']);

            $options = [];

            if (null !== $media) {
                if ($method === 'GET') {
                    $options['headers'] = [
                        'Accept' => $media
                    ];
                } else {
                    $options['headers'] = [
                        'Content-Type' => $media
                    ];
                }
            }

            if (null !== $body) {
                $options['body'] = $body;
            }

            try {
                $response = $this->client->request($registration, $method, $serviceUrl, $options, $scopes);
            } catch (LtiExceptionInterface $exception) {
                if ($exception->getPrevious() instanceof RequestException){
                    $response = $exception->getPrevious()->getResponse();
                } else {
                    throw $exception;
                }
            }

            $responseContentType = strtolower($response->getHeaderLine('Content-Type'));

            if(strpos($responseContentType, 'json')) {
                $format = 'json';
                $body = json_decode((string) $response->getBody(), true);
            } elseif (strpos($responseContentType, 'xml')) {
                $format = 'xml';
                $body = (string) $response->getBody();
            } else {
                $format = 'html';
                $body = (string) $response->getBody();
            }

            $serviceStatusCode = $response->getStatusCode();

            $serviceData = [
                'headers' => $response->getHeaders(),
                'code' => $serviceStatusCode,
                'format' => $format,
                'body' => $body
            ];

            if ($serviceStatusCode >= 200 && $serviceStatusCode < 300) {
                $flashType = 'success';
                $flashMessage = sprintf('LTI service success (%s)', $serviceStatusCode);
            } elseif ($serviceStatusCode >= 500) {
                $flashType = 'error';
                $flashMessage = sprintf('LTI service server error (%s)', $serviceStatusCode);
            } else {
                $flashType = 'warning';
                $flashMessage = sprintf('LTI service client error (%s)', $serviceStatusCode);
            }

            $this->flashBag->add($flashType, $flashMessage);
        } else {
            $form->setData($request->query->all());
        }

        return new Response(
            $this->twig->render(
                'tool/service/ltiServiceClient.html.twig',
                [
                    'form' => $form->createView(),
                    'formSubmitted' => $form->isSubmitted(),
                    'formShareUrl' => $this->generator->generate('tool_service_client', $form),
                    'serviceData' => $serviceData,
                ]
            )
        );
    }
}
