<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
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
