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

namespace App\Action\Platform\BasicOutcome;

use App\BasicOutcome\BasicOutcomeProcessor;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;
use Twig\Environment;

class DeleteBasicOutcomeAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var Environment */
    private $twig;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        FlashBagInterface $flashBag,
        CacheItemPoolInterface $cache,
        Environment $twig,
        RouterInterface $router
    ) {
        $this->flashBag = $flashBag;
        $this->cache = $cache;
        $this->twig = $twig;
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
