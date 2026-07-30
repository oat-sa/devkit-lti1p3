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
        return 'Get NRPS membership';
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
