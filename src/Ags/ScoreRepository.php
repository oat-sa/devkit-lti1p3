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

use OAT\Library\Lti1p3Ags\Model\Result\Result;
use OAT\Library\Lti1p3Ags\Model\Score\ScoreInterface;
use OAT\Library\Lti1p3Ags\Repository\ResultRepositoryInterface;
use OAT\Library\Lti1p3Ags\Repository\ScoreRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;

class ScoreRepository implements ScoreRepositoryInterface
{
    private const CACHE_KEY = 'lti1p3-ags-scores';

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var ResultRepositoryInterface|ResultRepository */
    private $repository;

    public function __construct(CacheItemPoolInterface $cache, ResultRepositoryInterface $repository)
    {
        $this->cache = $cache;
        $this->repository = $repository;
    }

    public function save(ScoreInterface $score): ScoreInterface
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

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
            'Auto generated result by LTI 1.3 DevKit'
        );

        $this->repository->save($result);

        return $score;
    }

    public function findByLineItemIdentifier(string $lineItemIdentifier): array
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $scores = $cache->get();

        return $scores[$lineItemIdentifier] ?? [];
    }
}
