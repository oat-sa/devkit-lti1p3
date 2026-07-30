<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Factory;

use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Scope;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ScopeRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ScopeRepositoryFactory
{
    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function create(): ScopeRepository
    {
        $scopes = [];

        foreach ($this->parameterBag->get('allowed_scopes') as $scope)  {
            $scopes = new Scope($scope);
        }

        return new ScopeRepository($scopes);
    }
}
