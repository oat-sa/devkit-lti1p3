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

namespace App\Action\Platform\Service\Ags;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class DeleteScoresAction
{
    /** @var RouterInterface */
    private $router;

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(RouterInterface $router, CacheItemPoolInterface $cache)
    {
        $this->router = $router;
        $this->cache = $cache;
    }

    public function __invoke(): RedirectResponse
    {
        $this->cache->deleteItem('lti1p3-ags-scores');

        return new RedirectResponse($this->router->generate('platform_ags_list_scores'));
    }
}
