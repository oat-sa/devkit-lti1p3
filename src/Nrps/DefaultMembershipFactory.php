<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
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
