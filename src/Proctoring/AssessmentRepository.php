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

namespace App\Proctoring;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Lock\LockFactory;

readonly class AssessmentRepository
{
    public const CACHE_KEY = 'lti1p3-proctoring-assessment';

    /** @var CacheItemPoolInterface */
    public function __construct(private CacheItemPoolInterface $cache, private LockFactory $lockFactory)
    {
    }

    public function find(string $assessmentIdentifier): ?Assessment
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        if ($cache->isHit()) {
            $assessments = $cache->get();

            if (array_key_exists($assessmentIdentifier, $assessments)) {
                return $assessments[$assessmentIdentifier];
            }
        }

        return null;
    }

    public function findAll(): array
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        if ($cache->isHit()) {
            return $cache->get();
        }

        return [];
    }

    public function save(Assessment $assessment): void
    {
        $lock = $this->lockFactory->createLock(self::CACHE_KEY);
        $cache = $this->cache->getItem(self::CACHE_KEY);
        $lock->acquire(true);

        $memberships = $cache->get();

        $memberships[$assessment->getIdentifier()] = $assessment;

        $cache->set($memberships);

        $this->cache->save($cache);
        $lock->release();
    }

    public function delete(Assessment $assessment): void
    {
        $lock = $this->lockFactory->createLock(self::CACHE_KEY);
        $lock->acquire(true);
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $memberships = $cache->get();

        unset($memberships[$assessment->getIdentifier()]);

        $cache->set($memberships);

        $this->cache->save($cache);
        $lock->release();
    }
}
