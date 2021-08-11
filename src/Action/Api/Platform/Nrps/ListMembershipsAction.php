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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
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
