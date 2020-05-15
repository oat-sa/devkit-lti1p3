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

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceToken;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class CreateScoreAction
{
    /** @var Security */
    private $security;

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(Security $security, CacheItemPoolInterface $cache)
    {
        $this->security = $security;
        $this->cache = $cache;
    }

    public function __invoke(Request $request, string $contextId, string $lineItemId): Response
    {
        /** @var LtiServiceToken $token */
        $token = $this->security->getToken();

        $item = $this->cache->getItem('lti1p3-ags-scores');

        $scores = $item->get();

        $scores[] = [
            'time' => time(),
            'registration' => $token->getRegistration()->getIdentifier(),
            'context' => $contextId,
            'lineItem' => $lineItemId,
            'data' => json_decode((string)$request->getContent(), true)
        ];

        $item->set($scores);

        $this->cache->save($item);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
