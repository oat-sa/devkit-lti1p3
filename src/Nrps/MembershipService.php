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
 * Copyright (c) 2022 (original work) Open Assessment Technologies SA;
 *
 * @author Sergei Mikhailov <sergei.mikhailov@taotesting.com>
 */

declare(strict_types=1);

namespace App\Nrps;

use OAT\Library\Lti1p3Nrps\Factory\Member\MemberFactoryInterface;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberCollection;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberInterface;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;

class MembershipService
{
    public const UPDATED_AT_FIELD = 'updated_at';

    /** @var MembershipRepository */
    private $repository;

    /** @var MemberFactoryInterface */
    private $memberFactory;

    public function __construct(MembershipRepository $repository, MemberFactoryInterface $memberFactory)
    {
        $this->repository = $repository;
        $this->memberFactory = $memberFactory;
    }

    public function findMembership(string $identifier, array $statuses = null): ?MembershipInterface
    {
        return $this->repository->find($identifier, $statuses);
    }

    public function updateMembership(MembershipInterface $membership, array $rawMembers): void
    {
        $memberCollection = new MemberCollection();
        $now = time();

        foreach ($rawMembers as $member) {
            unset($member[static::UPDATED_AT_FIELD]);
            $newMember = $this->memberFactory->create($member);

            if (!$membership->getMembers()->has($newMember->getUserIdentity()->getIdentifier())) {
                $this->setUpdatedAtToMember($newMember, $now);
            }

            $memberCollection->add($newMember);
        }

        /** @var MemberInterface $existingMember */
        foreach ($membership->getMembers() as $existingMember) {
            $existingMemberUpdatedAt = $existingMember->getProperties()->get(static::UPDATED_AT_FIELD);
            $existingMember->getProperties()->remove(static::UPDATED_AT_FIELD);
            $memberId = $existingMember->getUserIdentity()->getIdentifier();

            if (!$memberCollection->has($memberId)) {
                $this->setUpdatedAtToMember($existingMember, $now);
                $existingMember->setStatus(MemberInterface::STATUS_DELETED);

                $memberCollection->add($existingMember);
            } else {
                $member = $memberCollection->get($memberId);
                $this->setUpdatedAtToMember(
                    $member,
                    $existingMemberUpdatedAt && $member == $existingMember
                        ? $existingMemberUpdatedAt
                        : $now
                );
            }
        }

        $membership->setMembers($memberCollection);

        $this->repository->save($membership);
    }

    private function setUpdatedAtToMember(MemberInterface $member, int $now): void
    {
        $member->getProperties()->add([static::UPDATED_AT_FIELD => $now]);
    }
}
