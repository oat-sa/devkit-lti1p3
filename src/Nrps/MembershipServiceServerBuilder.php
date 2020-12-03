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

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\User\UserIdentityFactoryInterface;
use OAT\Library\Lti1p3Nrps\Model\Context\Context;
use OAT\Library\Lti1p3Nrps\Model\Member\Member;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberCollection;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberInterface;
use OAT\Library\Lti1p3Nrps\Model\Membership\Membership;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MembershipServiceServerBuilder
{
    /** @var RequestStack */
    private $requestStack;

    /** @var  ParameterBagInterface */
    private $parameterBag;

    /** @var  UserIdentityFactoryInterface */
    private $factory;

    public function __construct(
        RequestStack $requestStack,
        ParameterBagInterface $parameterBag,
        UserIdentityFactoryInterface $factory
    ) {
        $this->requestStack = $requestStack;
        $this->parameterBag = $parameterBag;
        $this->factory = $factory;
    }

    public function buildContextMembership(
        RegistrationInterface $registration,
        string $role = null,
        string $limit = null,
        string $offset = null
    ): MembershipInterface {

        $members = new MemberCollection();

        $users = array_filter(
            $this->parameterBag->get('users') ?? [],
            static function (array $userData) use ($role) {
                if (null === $role) {
                    return true;
                }

                return in_array($role, $userData['roles']);
            }
         );

        $slicedUsers = array_slice($users, $offset ? intval($offset) : 0, $limit ? intval($limit) : null);

        foreach ($slicedUsers as $userIdentifier => $userData) {
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

        $parsedUrl = parse_url(urldecode($this->requestStack->getCurrentRequest()->getUri()));
        parse_str($parsedUrl['query'] ?? '', $parsedQuery);

        $nextOffset = $limit ? intval($limit) : 0;
        if ($parsedQuery['offset'] ?? false) {
            $nextOffset += $parsedQuery['offset'];
        }

        $relUrl = sprintf(
            '%s://%s%s%s',
            $parsedUrl['scheme'],
            $parsedUrl['host'],
            $parsedUrl['port'] ?? false ? ':' . $parsedUrl['port'] : '',
            $parsedUrl['path']
        );

        $relUrlQuery = array_filter(
            [
                'role' => $role,
                'offset' => $nextOffset
            ]
        );

        return new Membership(
            $relUrl,
            new Context(
                sprintf('membership-registration-%s', $registration->getIdentifier()),
                'LTI 1.3 demo membership',
                sprintf('LTI 1.3 demo membership for registration %s', $registration->getIdentifier())
            ),
            $members,
            $nextOffset >= sizeof($users) ? null : $relUrl . '?' . http_build_query($relUrlQuery) . '; rel="next"'
        );
    }
}
