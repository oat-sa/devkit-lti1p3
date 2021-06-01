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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
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
