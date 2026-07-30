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
use App\Nrps\MembershipRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteMembershipAction implements ApiActionInterface
{
    /** @var MembershipRepository */
    private $repository;

    public function __construct(MembershipRepository $repository)
    {
        $this->repository = $repository;
    }

    public static function getName(): string
    {
        return 'Delete NRPS membership';
    }

    public function __invoke(Request $request, string $membershipIdentifier): Response
    {
        if ('default' === $membershipIdentifier) {
            throw new AccessDeniedHttpException('the membership with identifier default cannot be deleted');
        }

        $membership = $this->repository->find($membershipIdentifier);

        if (null === $membership) {
            throw new NotFoundHttpException(
                sprintf('cannot find membership with identifier %s', $membershipIdentifier)
            );
        }

        $this->repository->delete($membership);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
