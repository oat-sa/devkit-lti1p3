<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Statistics;

use App\Ags\LineItemRepository;
use App\Ags\ResultRepository;
use App\Ags\ScoreRepository;
use App\BasicOutcome\BasicOutcomeProcessor;
use App\Nrps\MembershipRepository;
use App\Proctoring\Assessment;
use App\Proctoring\AssessmentRepository;
use Psr\Cache\CacheItemPoolInterface;

class PlatformStatisticsCollector
{
    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function collect(): array
    {
        return [
            'nrpsMemberships' => $this->countCacheEntries(MembershipRepository::CACHE_KEY) + 1,
            'basicOutcomes' => $this->countCacheEntries(BasicOutcomeProcessor::CACHE_KEY),
            'agsLineItems' => $this->countCacheEntries(LineItemRepository::CACHE_KEY),
            'agsScores' => $this->countSubCacheEntries(ScoreRepository::CACHE_KEY),
            'agsResults' => $this->countSubCacheEntries(ResultRepository::CACHE_KEY),
            'acsAssessments' => $this->countCacheEntries(AssessmentRepository::CACHE_KEY),
            'acsControls' => $this->countAcsControls(),
        ];
    }

    private function countCacheEntries(string $cacheKey): int
    {
        $cacheData = $this->cache->getItem($cacheKey);

        if ($cacheData->isHit()) {
            return $cacheData->get() !== null
                ? sizeof($cacheData->get())
                : 0;
        }

        return 0;
    }

    private function countSubCacheEntries(string $cacheKey): int
    {
        $cacheData = $this->cache->getItem($cacheKey);

        if ($cacheData->isHit()) {
            $total = 0;

            foreach ($cacheData->get() ?? [] as $item) {
                $total += sizeof($item);
            }

            return $total;
        }

        return 0;
    }

    private function countAcsControls(): int
    {
        $cacheData = $this->cache->getItem(AssessmentRepository::CACHE_KEY);

        if ($cacheData->isHit()) {
            $total = 0;

            /** @var Assessment $assessment */
            foreach ($cacheData->get() ?? [] as $assessment) {
                $total += sizeof($assessment->getControls());
            }

            return $total;
        }

        return 0;
    }
}
