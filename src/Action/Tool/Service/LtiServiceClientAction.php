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
use Lcobucci\JWT\Builder;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Service\Client\ServiceClientInterface;
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

    /** @var ServiceClientInterface */
    private $client;

    /** @var Builder */
    private $builder;

    public function __construct(
        FlashBagInterface $flashBag,
        Environment $twig,
        FormFactoryInterface $factory,
        ServiceClientInterface $client,
        Builder $builder
    ) {
        $this->flashBag = $flashBag;
        $this->twig = $twig;
        $this->factory = $factory;
        $this->client = $client;
        $this->builder = $builder;
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
            $accept = $formData['accept'] ?? null;
            $body = $formData['body'] ?? null;
            $scopes = explode(' ', $formData['scope']);

            $options = [];

            if (null !== $accept) {
                $options['headers'] = [
                    'Accept' => $accept
                ];
            }

            if (null !== $body) {
                $options['body'] = $body;
            }

            $response = $this->client->request($registration, $method, $serviceUrl, $options, $scopes);

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

            $serviceData = [
                'headers' => $response->getHeaders(),
                'format' => $format,
                'body' => $body
            ];

            $this->flashBag->add('success', 'LTI service called with success');
        }

        return new Response(
            $this->twig->render(
                'tool/service/ltiServiceClient.html.twig',
                [
                    'form' => $form->createView(),
                    'serviceData' => $serviceData,
                ]
            )
        );
    }
}
