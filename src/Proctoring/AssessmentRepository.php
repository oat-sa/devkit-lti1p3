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

namespace App\Proctoring;

use Psr\Cache\CacheItemPoolInterface;

class AssessmentRepository
{
    public const CACHE_KEY = 'lti1p3-proctoring-assessment';

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
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
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $memberships = $cache->get();

        $memberships[$assessment->getIdentifier()] = $assessment;

        $cache->set($memberships);

        $this->cache->save($cache);
    }

    public function delete(Assessment $assessment): void
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        $memberships = $cache->get();

        unset($memberships[$assessment->getIdentifier()]);

        $cache->set($memberships);

        $this->cache->save($cache);
    }
}
