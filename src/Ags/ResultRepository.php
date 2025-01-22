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
 * Copyright (c) 2019-2025 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Ags;

use OAT\Library\Lti1p3Ags\Model\Result\ResultCollection;
use OAT\Library\Lti1p3Ags\Model\Result\ResultCollectionInterface;
use OAT\Library\Lti1p3Ags\Model\Result\ResultInterface;
use OAT\Library\Lti1p3Ags\Repository\ResultRepositoryInterface;
use OAT\Library\Lti1p3Core\Util\Generator\IdGeneratorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;

readonly class ResultRepository implements ResultRepositoryInterface
{
    public const CACHE_KEY = 'lti1p3-ags-results';

    public function __construct(
        private CacheItemPoolInterface $cache,
        private RequestStack $requestStack,
        private IdGeneratorInterface $generator,
        private LockFactory $lockFactory,
    ) {
    }

    public function save(ResultInterface $result): ResultInterface
    {
        $lock = $this->lockFactory->createLock(self::CACHE_KEY);
        $cache = $this->cache->getItem(self::CACHE_KEY);
        $lock->acquire(true);

        $results = $cache->get();

        if (null === $result->getIdentifier()) {
            $identifier = sprintf(
                '%s/%s',
                rtrim($this->requestStack->getCurrentRequest()->getUri(), '/'),
                $this->generator->generate()
            );

            $result->setIdentifier($identifier);
        }

        $results[$result->getLineItemIdentifier()][] = $result;

        $cache->set($results);

        $this->cache->save($cache);
        $lock->release();

        return $result;
    }

    public function findCollectionByLineItemIdentifier(
        string $lineItemIdentifier,
        ?int $limit = null,
        ?int $offset = null
    ): ResultCollectionInterface {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $lineItemResults = [];

        if ($cache->isHit()) {
            $results = $cache->get();

            $lineItemResults = $results[$lineItemIdentifier] ?? [];
        }

        return new ResultCollection(
            array_slice($lineItemResults, $offset ?: 0, $limit),
            $limit && ($limit + $offset) < sizeof($lineItemResults)
        );
    }

    public function findByLineItemIdentifierAndUserIdentifier(
        string $lineItemIdentifier,
        string $userIdentifier
    ): ?ResultInterface {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        if ($cache->isHit()) {
            $results = $cache->get();

            $foundResults = [];

            foreach ($results[$lineItemIdentifier] ?? [] as $result) {
                if ($result->getUserIdentifier() === $userIdentifier) {
                    $foundResults[] = $result;
                }
            }

            return !empty($foundResults) ? end($foundResults) : null;

        }

        return null;
    }

    public function deleteCollectionByLineItemIdentifier(string $lineItemIdentifier): void
    {
        $lock = $this->lockFactory->createLock(self::CACHE_KEY);
        $lock->acquire(true);
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $results = $cache->get();

        unset($results[$lineItemIdentifier]);

        $cache->set($results);

        $this->cache->save($cache);
        $lock->release();
    }
}
