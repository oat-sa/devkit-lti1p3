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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\HealthCheck;

use OAT\Library\HealthCheck\Checker\CheckerInterface;
use OAT\Library\HealthCheck\Result\CheckerResult;
use Psr\Cache\CacheItemPoolInterface;
use Throwable;

/**
 * Proves the Redis-backed application cache is actually usable
 * (write + read-back), so /health-check is a real readiness signal
 * instead of a server-level constant.
 */
class RedisCacheChecker implements CheckerInterface
{
    private const PROBE_KEY = 'health_check.probe';

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getIdentifier(): string
    {
        return 'redis_cache';
    }

    public function check(): CheckerResult
    {
        try {
            // Unique probe key per check: parallel workers/replicas share the
            // redis instance, and a shared key would let one check's write be
            // overwritten or deleted by another between save and read-back.
            $key = self::PROBE_KEY . '.' . uniqid('', true);
            $expected = (string) time();

            $item = $this->cache->getItem($key);
            $item->set($expected);
            $this->cache->save($item);

            $readBack = $this->cache->getItem($key);

            if (!$readBack->isHit() || $readBack->get() !== $expected) {
                return new CheckerResult(false, 'redis cache read-back mismatch');
            }

            $this->cache->deleteItem($key);

            return new CheckerResult(true, 'redis cache is reachable');
        } catch (Throwable $exception) {
            // the health endpoint is public: never leak DSN/exception details
            error_log(sprintf('redis health check failed: %s', $exception->getMessage()));

            return new CheckerResult(false, 'redis cache is unreachable (see application logs)');
        }
    }
}
