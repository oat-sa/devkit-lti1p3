<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Api\Platform\Nrps;

use App\Action\Api\ApiActionInterface;
use App\Generator\UrlGenerator;
use App\Nrps\DefaultMembershipFactory;
use App\Nrps\MembershipRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListMembershipsAction implements ApiActionInterface
{
    /** @var MembershipRepository */
    private $repository;

    /** @var DefaultMembershipFactory */
    private $factory;

    /** @var UrlGenerator */
    private $generator;

    public function __construct(
        MembershipRepository $repository,
        DefaultMembershipFactory $factory,
        UrlGenerator $generator
    ) {
        $this->repository = $repository;
        $this->factory = $factory;
        $this->generator = $generator;
    }

    public static function getName(): string
    {
        return 'List NRPS memberships';
    }

    public function __invoke(Request $request): Response
    {
        $limit = $request->query->has('limit') ? intval($request->query->get('limit')) : null;
        $offset = $request->query->has('offset') ? intval($request->query->get('offset')) : null;

        $memberships = $this->repository->findAll();

        array_unshift($memberships, $this->factory->create());

        $memberships = array_slice($memberships, $offset ?: 0, $limit);

        $membershipsList = [];

        foreach ($memberships as $membership) {
            $membershipsList[] = [
                'membership' => $membership,
                'nrps_url' => $this->generator->generate(
                    'platform_service_nrps',
                    [
                        'contextIdentifier' => $membership->getContext()->getIdentifier(),
                        'membershipIdentifier' => $membership->getIdentifier(),
                    ]
                )
            ];
        }

        return new JsonResponse(
            [
                'memberships' => $membershipsList
            ]
        );
    }
}
