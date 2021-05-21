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

namespace App\Ags;

use OAT\Library\Lti1p3Ags\Model\Result\ResultCollectionInterface;
use OAT\Library\Lti1p3Ags\Model\Result\ResultInterface;
use OAT\Library\Lti1p3Ags\Repository\ResultRepositoryInterface;
use OAT\Library\Lti1p3Core\Util\Generator\IdGeneratorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ResultRepository implements ResultRepositoryInterface
{
    private const CACHE_KEY = 'lti1p3-ags-results';

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(
        CacheItemPoolInterface $cache,
        RequestStack $requestStack,
        IdGeneratorInterface $generator
    ) {
        $this->cache = $cache;
        $this->requestStack = $requestStack;
        $this->generator = $generator;
    }

    public function save(ResultInterface $result): ResultInterface
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $results = $cache->get();

        $results[$result->getLineItemIdentifier()][] = $result;

        $cache->set($results);

        $this->cache->save($cache);

        return $result;
    }

    public function findBy(
        string $lineItemIdentifier,
        ?string $userIdentifier = null,
        ?int $limit = null,
        ?int $offset = null
    ): ?ResultCollectionInterface {

    }
}
