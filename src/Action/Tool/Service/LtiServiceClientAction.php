<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
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
use Symfony\Component\Stopwatch\Stopwatch;
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

        if (!$form->isSubmitted()) {
            $form->setData($request->query->all());
        } elseif ($form->isValid()) {

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

            $stopwatch = new Stopwatch();

            $stopwatch->start('serviceCall');

            try {
                $response = $this->client->request($registration, $method, $serviceUrl, $options, $scopes);
            } catch (LtiExceptionInterface $exception) {
                $previous = $exception->getPrevious();
                if ($previous instanceof RequestException && $previous->getResponse() !== null) {
                    $response = $previous->getResponse();
                } else {
                    throw $exception;
                }
            }

            $stopWatchEvent = $stopwatch->stop('serviceCall');

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
                'duration' => $stopWatchEvent->getDuration(),
                'memory' => $stopWatchEvent->getMemory(),
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
