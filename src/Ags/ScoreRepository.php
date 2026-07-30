<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Ags;

use OAT\Library\Lti1p3Ags\Model\Result\Result;
use OAT\Library\Lti1p3Ags\Model\Score\ScoreInterface;
use OAT\Library\Lti1p3Ags\Repository\ResultRepositoryInterface;
use OAT\Library\Lti1p3Ags\Repository\ScoreRepositoryInterface;
use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use OAT\Library\Lti1p3Core\Util\Collection\CollectionInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Lock\LockFactory;

readonly class ScoreRepository implements ScoreRepositoryInterface
{
    public const CACHE_KEY = 'lti1p3-ags-scores';

    public function __construct(
        private CacheItemPoolInterface $cache,
        private ResultRepositoryInterface $resultRepository,
        private LockFactory $lockFactory,
    ) {
    }

    public function save(ScoreInterface $score): ScoreInterface
    {
        $lock = $this->lockFactory->createLock(self::CACHE_KEY);
        $cache = $this->cache->getItem(self::CACHE_KEY);
        $lock->acquire(true);

        $scores = $cache->get();

        $scores[$score->getLineItemIdentifier()][] = $score;

        $cache->set($scores);

        $this->cache->save($cache);

        $result = new Result(
            $score->getUserIdentifier(),
            $score->getLineItemIdentifier(),
            null,
            $score->getScoreGiven(),
            $score->getScoreMaximum(),
            'Auto generated result by LTI 1.3 DevKit',
            $score->getAdditionalProperties()->all()
        );

        $this->resultRepository->save($result);
        $lock->release();

        return $score;
    }

    public function findCollectionByLineItemIdentifier(string $lineItemIdentifier): CollectionInterface
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $scores = $cache->get();

        return (new Collection)->add($scores[$lineItemIdentifier] ?? []);
    }

    public function deleteCollectionByLineItemIdentifier(string $lineItemIdentifier): void
    {
        $lock = $this->lockFactory->createLock(self::CACHE_KEY);
        $cache = $this->cache->getItem(self::CACHE_KEY);
        $lock->acquire(true);

        $scores = $cache->get();

        unset($scores[$lineItemIdentifier]);

        $cache->set($scores);

        $this->cache->save($cache);
        $lock->release();
    }
}
