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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
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
