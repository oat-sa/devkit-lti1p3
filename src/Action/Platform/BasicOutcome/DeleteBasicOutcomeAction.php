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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

class DeleteBasicOutcomeAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        FlashBagInterface $flashBag,
        CacheItemPoolInterface $cache,
        RouterInterface $router
    ) {
        $this->flashBag = $flashBag;
        $this->cache = $cache;
        $this->router = $router;
    }

    public function __invoke(Request $request, string $basicOutcomeIdentifier): Response
    {
        try {
            $basicOutcomeCache = $this->cache->getItem(BasicOutcomeProcessor::CACHE_KEY);

            $basicOutcomeList = [];

            if ($basicOutcomeCache->isHit()) {
                $basicOutcomeList = $basicOutcomeCache->get();
            }

            unset($basicOutcomeList[$basicOutcomeIdentifier]);

            $basicOutcomeCache->set($basicOutcomeList);

            $this->cache->save($basicOutcomeCache);

            $this->flashBag->add('success', sprintf('Basic outcome %s deletion success', $basicOutcomeIdentifier));
        } catch (Throwable $exception) {
            $this->flashBag->add('error', $exception->getMessage());
        }

        return new RedirectResponse($this->router->generate('platform_basic_outcome_list'));
    }
}
