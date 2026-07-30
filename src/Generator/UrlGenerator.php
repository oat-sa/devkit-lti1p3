<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Generator;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\RouterInterface;

class UrlGenerator
{
    /** @var RouterInterface */
    private $router;

    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(RouterInterface $router, ParameterBagInterface $parameterBag)
    {
        $this->router = $router;
        $this->parameterBag = $parameterBag;
    }

    public function generate(string $routeName, array $routeParameters = []): string
    {
        if ($this->parameterBag->has('application_host')) {
            return sprintf(
                '%s%s',
                $this->parameterBag->get('application_host'),
                $this->router->generate($routeName, $routeParameters, RouterInterface::ABSOLUTE_PATH)
            );
        }

        return $this->router->generate($routeName, $routeParameters, RouterInterface::ABSOLUTE_URL);
    }
}
