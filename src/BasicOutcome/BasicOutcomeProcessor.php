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

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use OAT\Library\Lti1p3BasicOutcome\Service\Server\Processor\BasicOutcomeServiceServerProcessorInterface;
use OAT\Library\Lti1p3BasicOutcome\Service\Server\Processor\BasicOutcomeServiceServerProcessorResult;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Security;

class BasicOutcomeProcessor implements BasicOutcomeServiceServerProcessorInterface
{
    public const CACHE_KEY = 'lti1p3-nrps-basic-outcomes';

    /** @var Security */
    private $security;

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(Security $security, CacheItemPoolInterface $cache)
    {
        $this->security = $security;
        $this->cache = $cache;
    }

    public function processReadResult(string $sourcedId): BasicOutcomeServiceServerProcessorResult
    {
        $basicOutcomeCache = $this->cache->getItem(self::CACHE_KEY);

        if ($basicOutcomeCache->isHit()) {

            $basicOutcomeList = $basicOutcomeCache->get();
            $basicOutcomeKey = $this->getBasicOutcomeKey($sourcedId);

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

    public function processReplaceResult(string $sourcedId, float $score, string $language = 'en'): BasicOutcomeServiceServerProcessorResult
    {
        /** @var LtiServiceSecurityToken $token */
        $token = $this->security->getToken();

        $basicOutcomeCache = $this->cache->getItem(self::CACHE_KEY);

        $basicOutcomeList = [];

        if ($basicOutcomeCache->isHit()) {
            $basicOutcomeList = $basicOutcomeCache->get();
        }

        $basicOutcomeKey = $this->getBasicOutcomeKey($sourcedId);

        $basicOutcomeList[$basicOutcomeKey] = [
            'score' => $score,
            'language' => $language,
            'registration' => $token->getRegistration()->getIdentifier(),
            'tool' => $token->getRegistration()->getTool()->getIdentifier(),
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

    public function processDeleteResult(string $sourcedId): BasicOutcomeServiceServerProcessorResult
    {
        $basicOutcomeCache = $this->cache->getItem(self::CACHE_KEY);

        $basicOutcomeKey = $this->getBasicOutcomeKey($sourcedId);

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

    private function getBasicOutcomeKey(string $sourcedId): string
    {
        /** @var LtiServiceSecurityToken $token */
        $token = $this->security->getToken();

        return sprintf('%s-%s', $token->getRegistration()->getIdentifier(), $sourcedId);
    }
}
