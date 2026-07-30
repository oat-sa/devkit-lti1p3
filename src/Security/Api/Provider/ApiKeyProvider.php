<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

namespace App\Security\Api\Provider;

use App\Security\Api\Token\ApiKeyToken;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiKeyProvider implements AuthenticationProviderInterface
{
    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function supports(TokenInterface $token): bool
    {
        return $token instanceof ApiKeyToken;
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        $securedApiKey = $this->parameterBag->get('application_api_key');

        if ($token->getAttribute('api_key') === $securedApiKey)
        {
            $token->setAuthenticated(true);

            return $token;
        }

        throw new AuthenticationException('Unauthorised api key');
    }
}
