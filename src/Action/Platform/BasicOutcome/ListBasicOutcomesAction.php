<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Platform\BasicOutcome;

use App\BasicOutcome\BasicOutcomeProcessor;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ListBasicOutcomesAction
{
    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var Environment */
    private $twig;

    public function __construct(CacheItemPoolInterface $cache, Environment $twig)
    {
        $this->cache = $cache;
        $this->twig = $twig;
    }

    public function __invoke(Request $request): Response
    {
        return new Response(
            $this->twig->render(
                'platform/basicOutcome/listBasicOutcomes.html.twig',
                [
                    'basicOutcomes' => $this->cache->getItem(BasicOutcomeProcessor::CACHE_KEY)->get()
                ]
            )
        );
    }
}
