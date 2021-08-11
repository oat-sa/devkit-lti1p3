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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetMembershipAction implements ApiActionInterface
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
        return 'NRPS membership retrieval';
    }

    public function __invoke(Request $request, string $membershipIdentifier): Response
    {
        if ('default' === $membershipIdentifier) {
            $membership = $this->factory->create();
        } else {
            $membership = $this->repository->find($membershipIdentifier);

            if (null === $membership) {
                throw new NotFoundHttpException(
                    sprintf('cannot find membership with identifier %s', $membershipIdentifier)
                );
            }
        }

        return new JsonResponse(
            [
                'membership' => $membership,
                'nrps_url' => $this->generator->generate(
                    'platform_service_nrps',
                    [
                        'contextIdentifier' => $membership->getContext()->getIdentifier(),
                        'membershipIdentifier' => $membership->getIdentifier(),
                    ]
                )
            ]
        );
    }
}
