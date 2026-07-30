<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Action\Api\ApiActionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class ErrorHandlerSubscriber implements EventSubscriberInterface
{
    /** @var Environment */
    private $twig;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Environment $twig, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        $this->logger->error($exception->getMessage());

        $code = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $controller = $request->attributes->get('_controller');

        if (is_a($controller, ApiActionInterface::class, true) || 0 === strpos($request->getPathInfo(), '/api/')) {

            $errorMessage = null === $controller
                ? sprintf('Api error: %s', $exception->getMessage())
                : sprintf('%s api error: %s', $controller::getName(), $exception->getMessage());

            $event->setResponse(
                new JsonResponse(
                    [
                        'error' => $errorMessage,
                    ],
                    $code
                )
            );
        } else {
            if ($request->isXmlHttpRequest()) {
                $event->setResponse(
                    new JsonResponse(
                        [
                            'code' => $code,
                            'message' => $exception->getMessage(),
                        ]
                    )
                );
            } else {
                $event->setResponse(
                    new Response(
                        $this->twig->render(
                            'error/error.html.twig',
                            [
                                'code' => $code,
                                'message' => $exception->getMessage(),
                            ]
                        )
                    )
                );
            }
        }
    }
}
