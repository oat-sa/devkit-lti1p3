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

namespace App\BasicOutcome;

use Carbon\Carbon;
use OAT\Library\Lti1p3BasicOutcome\Service\Server\Processor\BasicOutcomeServiceServerProcessorInterface;
use OAT\Library\Lti1p3BasicOutcome\Service\Server\Processor\BasicOutcomeServiceServerProcessorResult;
use Psr\Cache\CacheItemPoolInterface;

class BasicOutcomeProcessor implements BasicOutcomeServiceServerProcessorInterface
{
    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function processReadResult(string $sourcedId): BasicOutcomeServiceServerProcessorResult
    {
        $cacheKey = sprintf('lti1p3-basic-outcome-%s', $sourcedId);

        if ($this->cache->hasItem($cacheKey)) {

            $itemData = $this->cache->getItem($cacheKey)->get();

            $score = floatval($itemData['score']);
            $language = $itemData['language'] ?? null;

            return new BasicOutcomeServiceServerProcessorResult(
                true,
                sprintf('The current score for sourced id %s is %s and for language %s', $sourcedId, $score, $language),
                $score,
                $language
            );
        }

        return new BasicOutcomeServiceServerProcessorResult(
            true,
            sprintf('The current score for sourced id %s is non existing', $sourcedId)
        );
    }

    public function processReplaceResult(string $sourcedId, float $score, string $language = 'en'): BasicOutcomeServiceServerProcessorResult
    {
        $item = $this->cache->getItem(sprintf('lti1p3-basic-outcome-%s', $sourcedId));

        $item
            ->set([
                'score' => $score,
                'language' => $language
            ])
            ->expiresAt(
                Carbon::now()->addSeconds(3600)
            );

        $this->cache->save($item);

        return new BasicOutcomeServiceServerProcessorResult(
            true,
            sprintf('The new score for sourced id %s is now %s and for language %s', $sourcedId, $score, $language),
            $score,
            $language
        );
    }

    public function processDeleteResult(string $sourcedId): BasicOutcomeServiceServerProcessorResult
    {
        $this->cache->deleteItem(sprintf('lti1p3-basic-outcome-%s', $sourcedId));

        return new BasicOutcomeServiceServerProcessorResult(
            true,
            sprintf('The score for sourced id %s has been deleted', $sourcedId)
        );
    }
}
