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

namespace App\Action;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class HomeAction
{
    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var Environment */
    private $twig;

    public function __construct(ParameterBagInterface $parameterBag, Environment $twig)
    {
        $this->parameterBag = $parameterBag;
        $this->twig = $twig;
    }

    public function __invoke(Request $request): Response
    {
        return new Response(
            $this->twig->render(
                'home/home.html.twig',
                [
                    'configuration' => $this->parameterBag->get('lti1p3_resolved_configuration')
                ]
            )
        );
    }
}
