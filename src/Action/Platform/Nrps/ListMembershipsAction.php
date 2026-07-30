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
use Twig\Environment;

class ListMembershipsAction
{
    /** @var MembershipRepository */
    private $repository;

    /** @var DefaultMembershipFactory */
    private $factory;

    /** @var Environment */
    private $twig;

    public function __construct(MembershipRepository $repository, DefaultMembershipFactory $factory, Environment $twig)
    {
        $this->repository = $repository;
        $this->factory = $factory;
        $this->twig = $twig;
    }

    public function __invoke(Request $request): Response
    {
        return new Response(
            $this->twig->render(
                'platform/nrps/listMemberships.html.twig',
                [
                    'defaultMembership' => $this->factory->create(),
                    'memberships' => $this->repository->findAll()
                ]
            )
        );
    }
}
