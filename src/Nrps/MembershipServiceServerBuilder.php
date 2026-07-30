<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Nrps;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberCollection;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberInterface;
use OAT\Library\Lti1p3Nrps\Model\Membership\MembershipInterface;
use OAT\Library\Lti1p3Nrps\Service\Server\Builder\MembershipServiceServerBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MembershipServiceServerBuilder implements MembershipServiceServerBuilderInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var MembershipService */
    private $service;

    /** @var DefaultMembershipFactory */
    private $factory;

    public function __construct(
        RequestStack $requestStack,
        MembershipService $service,
        DefaultMembershipFactory $factory
    ) {
        $this->requestStack = $requestStack;
        $this->service = $service;
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

        $parsedUrl = parse_url(urldecode($request->getUri()));
        parse_str($parsedUrl['query'] ?? '', $parsedQuery);

        $since = isset($parsedQuery['since']) ? (int)$parsedQuery['since'] : null;

        $membershipIdentifier = $routeParameters['membershipIdentifier'] ?? null;
        $contextIdentifier = $routeParameters['contextIdentifier'] ?? null;

        if ($membershipIdentifier === 'default') {
            $membership = $this->factory->create();
        } else {
            $membership = $this->service->findMembership($membershipIdentifier);
        }

        if (null === $membership || $membership->getContext()->getIdentifier() !== $contextIdentifier) {
            throw new NotFoundHttpException(
                sprintf('Membership with context %s and identifier %s cannot be found', $contextIdentifier, $membershipIdentifier)
            );
        }

        $filteredMembers = array_filter(
            $membership->getMembers()->all(),
            static function (MemberInterface $member) use ($role, $since) {
                return ($role === null || in_array($role, $member->getRoles(), true))
                       && (
                           $since === null
                           || $member->getProperties()->get(MembershipService::UPDATED_AT_FIELD, 0) > $since
                       )
                       && ($since !== null || $member->getStatus() !== MemberInterface::STATUS_DELETED);
            }
        );

        return $membership
            ->setMembers(new MemberCollection(array_slice($filteredMembers, $offset ?? 0, $limit ?? null)))
            ->setRelationLink(
                $this->buildRelationLink($parsedUrl, sizeof($filteredMembers), $role, $limit, $offset, $since)
            );
    }

    private function buildRelationLink(
        array $parsedUrl,
        int $totalCount,
        string $role = null,
        int $limit = null,
        int $offset = null,
        int $since = null
    ): ?string {
        $linkUrl = sprintf(
            '%s://%s%s%s',
            $parsedUrl['scheme'],
            $parsedUrl['host'],
            $parsedUrl['port'] ?? false ? ':' . $parsedUrl['port'] : '',
            $parsedUrl['path']
        );

        $linkQueryParameters = [
            'differences' => array_filter(
                [
                    'role' => $role,
                    'limit' => $limit,
                    'since' => time()
                ]
            ),
        ];

        if ($limit && ($nextOffset = $limit + ($offset ?? 0)) < $totalCount) {
            $linkQueryParameters['next'] = array_filter(
                [
                    'role' => $role,
                    'limit' => $limit,
                    'offset' => $nextOffset,
                    'since' => $since
                ]
            );
        }

        $links = [];
        foreach ($linkQueryParameters as $link => $queryParameters) {
            $links[] = sprintf('<%s?%s>; rel="%s"', $linkUrl, http_build_query($queryParameters), $link);
        }

        return implode(', ', $links);
    }
}
