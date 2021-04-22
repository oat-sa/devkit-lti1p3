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

use OAT\Library\Lti1p3BasicOutcome\Service\Server\Processor\BasicOutcomeServiceServerProcessorInterface;
use OAT\Library\Lti1p3BasicOutcome\Service\Server\Processor\Result\BasicOutcomeServiceServerProcessorResult;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use Psr\Cache\CacheItemPoolInterface;

class BasicOutcomeProcessor implements BasicOutcomeServiceServerProcessorInterface
{
    public const CACHE_KEY = 'lti1p3-basic-outcomes';

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function processReadResult(
        RegistrationInterface $registration,
        string $sourcedId
    ): BasicOutcomeServiceServerProcessorResult {
        $basicOutcomeCache = $this->cache->getItem(self::CACHE_KEY);

        if ($basicOutcomeCache->isHit()) {

            $basicOutcomeList = $basicOutcomeCache->get();
            $basicOutcomeKey = $this->getBasicOutcomeKey($registration, $sourcedId);

            if (array_key_exists($basicOutcomeKey, $basicOutcomeList)) {

                $score = floatval($basicOutcomeList[$basicOutcomeKey]['score']);
                $language = $basicOutcomeList[$basicOutcomeKey]['language'] ?? null;

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

        return new BasicOutcomeServiceServerProcessorResult(
            true,
            sprintf('The current score for sourced id %s is non existing', $sourcedId)
        );
    }

    public function processReplaceResult(
        RegistrationInterface $registration,
        string $sourcedId,
        float $score,
        string $language = 'en'
    ): BasicOutcomeServiceServerProcessorResult {
        $basicOutcomeCache = $this->cache->getItem(self::CACHE_KEY);

        $basicOutcomeList = [];

        if ($basicOutcomeCache->isHit()) {
            $basicOutcomeList = $basicOutcomeCache->get();
        }

        $basicOutcomeKey = $this->getBasicOutcomeKey($registration, $sourcedId);

        $basicOutcomeList[$basicOutcomeKey] = [
            'score' => $score,
            'language' => $language,
            'registration' => $registration->getIdentifier(),
            'tool' => $registration->getTool()->getIdentifier(),
        ];

        $basicOutcomeCache->set($basicOutcomeList);

        $this->cache->save($basicOutcomeCache);

        return new BasicOutcomeServiceServerProcessorResult(
            true,
            sprintf('The new score for sourced id %s is now %s and for language %s', $sourcedId, $score, $language),
            $score,
            $language
        );
    }

    public function processDeleteResult(
        RegistrationInterface $registration,
        string $sourcedId
    ): BasicOutcomeServiceServerProcessorResult {
        $basicOutcomeCache = $this->cache->getItem(self::CACHE_KEY);

        $basicOutcomeKey = $this->getBasicOutcomeKey($registration, $sourcedId);

        $basicOutcomeList = [];

        if ($basicOutcomeCache->isHit()) {
            $basicOutcomeList = $basicOutcomeCache->get();
        }

        unset($basicOutcomeList[$basicOutcomeKey]);

        $basicOutcomeCache->set($basicOutcomeList);

        $this->cache->save($basicOutcomeCache);

        return new BasicOutcomeServiceServerProcessorResult(
            true,
            sprintf('The score for sourced id %s has been deleted', $sourcedId)
        );
    }

    private function getBasicOutcomeKey(RegistrationInterface $registration, string $sourcedId): string
    {
        return sprintf('%s-%s', $registration->getIdentifier(), $sourcedId);
    }
}
