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
use OAT\Library\Lti1p3Nrps\Model\Member\MemberCollection;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberInterface;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;
use OAT\Library\Lti1p3Nrps\Service\Server\Builder\MembershipServiceServerBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MembershipServiceServerBuilder implements MembershipServiceServerBuilderInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var MembershipRepository */
    private $repository;

    /** @var DefaultMembershipFactory */
    private $factory;

    public function __construct(
        RequestStack $requestStack,
        MembershipRepository $repository,
        DefaultMembershipFactory $factory
    ) {
        $this->requestStack = $requestStack;
        $this->repository = $repository;
        $this->factory = $factory;
    }

    public function buildResourceLinkMembership(
        RegistrationInterface $registration,
        string $resourceLinkIdentifier,
        string $role = null,
        int $limit = null,
        int $offset = null
    ): MembershipInterface {
        return $this->build($role, $limit, $offset);
    }

    public function buildContextMembership(
        RegistrationInterface $registration,
        string $role = null,
        int $limit = null,
        int $offset = null
    ): MembershipInterface {
        return $this->build($role, $limit, $offset);
    }

    private function build(string $role = null, int $limit = null, int $offset = null): MembershipInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $routeParameters = $request->attributes->get('_route_params');

        $membershipIdentifier = $routeParameters['membershipIdentifier'] ?? null;
        $contextIdentifier = $routeParameters['contextIdentifier'] ?? null;

        if ($membershipIdentifier === 'default') {
            $membership = $this->factory->create();
        } else {
            $membership = $this->repository->find($membershipIdentifier);
        }

        if (null === $membership || $membership->getContext()->getIdentifier() !== $contextIdentifier) {
            throw new NotFoundHttpException(
                sprintf('Membership with context %s and identifier %s cannot be found', $contextIdentifier, $membershipIdentifier)
            );
        }

        $filteredMembers = array_filter(
            $membership->getMembers()->all(),
            static function (MemberInterface $member) use ($role) {
                if (null === $role) {
                    return true;
                }

                return in_array($role, $member->getRoles());
            }
        );

        return $membership
            ->setMembers(new MemberCollection(array_slice($filteredMembers, $offset ?? 0, $limit ?? null)))
            ->setRelationLink($this->buildRelationLink($request, sizeof($filteredMembers), $role, $limit, $offset));
    }

    private function buildRelationLink(
        Request $request,
        int $totalCount,
        string $role = null,
        int $limit = null,
        int $offset = null
    ): ?string {
        if ((($limit ?? 0) + ($offset ?? 0)) < $totalCount) {
            $parsedUrl = parse_url(urldecode($request->getUri()));
            parse_str($parsedUrl['query'] ?? '', $parsedQuery);

            $nextOffset = $limit ?? 0;

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

            return $relUrl . '?' . http_build_query($relUrlQuery) . '; rel="next"';
        }

        return null;
    }
}
