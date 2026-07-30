<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
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
