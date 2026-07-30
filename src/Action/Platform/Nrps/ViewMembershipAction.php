<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Platform\Nrps;

use App\Nrps\DefaultMembershipFactory;
use App\Nrps\MembershipRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class ViewMembershipAction
{
    /** @var MembershipRepository */
    private $repository;

    /** @var Environment */
    private $twig;

    /** @var DefaultMembershipFactory */
    private $membershipFactory;

    public function __construct(
        MembershipRepository $repository,
        Environment $twig,
        DefaultMembershipFactory $membershipFactory
    ) {
        $this->repository = $repository;
        $this->twig = $twig;
        $this->membershipFactory = $membershipFactory;
    }

    public function __invoke(Request $request, string $membershipIdentifier): Response
    {
        if ($membershipIdentifier === 'default') {
            $membership = $this->membershipFactory->create();
        } else {
            $membership = $this->repository->find($membershipIdentifier);
        }

        if (null === $membership) {
            throw new NotFoundHttpException(
                sprintf('Cannot find membership with id %s', $membershipIdentifier)
            );
        }

        return new Response(
            $this->twig->render(
                'platform/nrps/viewMembership.html.twig',
                [
                    'membership' => $membership
                ]
            )
        );
    }
}
