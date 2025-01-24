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

namespace App\Nrps;

use OAT\Library\Lti1p3Nrps\Model\Member\MemberCollection;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberInterface;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Lock\LockFactory;

readonly class MembershipRepository
{
    public const CACHE_KEY = 'lti1p3-nrps-memberships';

    /** @var CacheItemPoolInterface */
    public function __construct(private CacheItemPoolInterface $cache, private LockFactory $lockFactory)
    {
    }

    public function find(
        string $identifier,
        ?array $statuses = [MemberInterface::STATUS_ACTIVE, MemberInterface::STATUS_INACTIVE]
    ): ?MembershipInterface {
        $membership = null;
        $cache = $this->cache->getItem(self::CACHE_KEY);

        if ($cache->isHit()) {
            $memberships = $cache->get();

            if (array_key_exists($identifier, $memberships)) {
                /** @var MembershipInterface $membership */
                $membership = $memberships[$identifier];

                if (null !== $statuses) {
                    $membership->setMembers(
                        new MemberCollection(
                            array_filter(
                                $membership->getMembers()->all(),
                                static function (MemberInterface $member) use ($statuses) {
                                    return in_array($member->getStatus(), $statuses, true);
                                }
                            )
                        )
                    );
                }
            }
        }

        return $membership;
    }

    public function findAll(): array
    {
        $cache = $this->cache->getItem(self::CACHE_KEY);

        if ($cache->isHit()) {
            return $cache->get();
        }

        return [];
    }

    public function save(MembershipInterface $membership): void
    {
        $lock = $this->lockFactory->createLock(self::CACHE_KEY);
        $cache = $this->cache->getItem(self::CACHE_KEY);
        $lock->acquire(true);

        $memberships = $cache->get();

        $memberships[$membership->getIdentifier()] = $membership;

        $cache->set($memberships);

        $this->cache->save($cache);
        $lock->release();
    }

    public function delete(MembershipInterface $membership): void
    {
        $lock = $this->lockFactory->createLock(self::CACHE_KEY);
        $cache = $this->cache->getItem(self::CACHE_KEY);
        $lock->acquire(true);

        $memberships = $cache->get();

        unset($memberships[$membership->getIdentifier()]);

        $cache->set($memberships);

        $this->cache->save($cache);
        $lock->release();
    }
}
