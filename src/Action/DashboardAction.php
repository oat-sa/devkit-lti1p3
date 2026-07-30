<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action;

use App\Statistics\PlatformStatisticsCollector;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class DashboardAction
{
    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var Environment */
    private $twig;

    /** @var PlatformStatisticsCollector */
    private $collector;

    public function __construct(
        ParameterBagInterface $parameterBag,
        Environment $twig,
        PlatformStatisticsCollector $collector
    ) {
        $this->parameterBag = $parameterBag;
        $this->twig = $twig;
        $this->collector = $collector;
    }

    public function __invoke(Request $request): Response
    {
        return new Response(
            $this->twig->render(
                'dashboard/dashboard.html.twig',
                [
                    'configuration' => $this->parameterBag->get('lti1p3_resolved_configuration'),
                    'statistics' => $this->collector->collect(),
                    'users' => $this->parameterBag->get('users') ?? []
                ]
            )
        );
    }
}
