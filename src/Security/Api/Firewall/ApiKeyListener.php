<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

namespace App\Security\Api\Firewall;

use App\Security\Api\Token\ApiKeyToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiKeyListener
{
    private const AUTHORIZATION_HEADER = 'Authorization';

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var AuthenticationManagerInterface  */
    private $authenticationManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        LoggerInterface $logger
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->logger = $logger;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->headers->has(static::AUTHORIZATION_HEADER)) {
            return;
        }

        $apiKey = substr($request->headers->get(static::AUTHORIZATION_HEADER), strlen('Bearer '));

        $token = new ApiKeyToken();
        $token->setAttribute('api_key', $apiKey);

        try {
            $authToken = $this->authenticationManager->authenticate($token);
            $this->tokenStorage->setToken($authToken);

            return;
        } catch (AuthenticationException $exception) {
            $this->logger->error($exception->getMessage());

            $response = new JsonResponse(
                [
                    'error' => $exception->getMessage()
                ],
                Response::HTTP_UNAUTHORIZED
            );

            $event->setResponse($response);
        }
    }
}
