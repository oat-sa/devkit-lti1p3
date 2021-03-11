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

namespace App\Nrps;

use OAT\Library\Lti1p3Core\User\UserIdentityFactoryInterface;
use OAT\Library\Lti1p3Nrps\Model\Context\Context;
use OAT\Library\Lti1p3Nrps\Model\Member\Member;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberCollection;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberInterface;
use OAT\Library\Lti1p3Nrps\Model\Membership\Membership;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DefaultMembershipFactory
{
    /** @var  ParameterBagInterface */
    private $parameterBag;

    /** @var  UserIdentityFactoryInterface */
    private $factory;

    public function __construct(ParameterBagInterface $parameterBag, UserIdentityFactoryInterface $factory)
    {
        $this->parameterBag = $parameterBag;
        $this->factory = $factory;
    }

    public function create(): MembershipInterface
    {
        $members = new MemberCollection();

        foreach ($this->parameterBag->get('users') ?? [] as $userIdentifier => $userData) {
            $userIdentity = $this->factory->create(
                $userIdentifier,
                $userData['name'] ?? null,
                $userData['email'] ?? null,
                $userData['givenName'] ?? null,
                $userData['familyName'] ?? null,
                $userData['middleName'] ?? null,
                $userData['locale'] ?? null,
                $userData['picture'] ?? null
            );

            $members->add(
                new Member(
                    $userIdentity,
                    MemberInterface::STATUS_ACTIVE,
                    $userData['roles'] ?? [],
                    ['user_id' => $userIdentifier] + $userData
                )
            );
        }

        return new Membership(
            'default',
            new Context('default', 'Default context label', 'Default context title'),
            $members
        );
    }
}
