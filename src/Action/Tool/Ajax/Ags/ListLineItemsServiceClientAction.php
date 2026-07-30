<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Tool\Ajax\Ags;

use OAT\Library\Lti1p3Ags\Service\LineItem\Client\LineItemServiceClient;
use OAT\Library\Lti1p3Ags\Voter\ScopePermissionVoter;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ListLineItemsServiceClientAction
{
    /** @var Environment */
    private $twig;

    /** @var LineItemServiceClient */
    private $client;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(
        Environment $twig,
        LineItemServiceClient $client,
        RegistrationRepositoryInterface $repository
    ) {
        $this->twig = $twig;
        $this->client = $client;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $registration = $this->repository->find($request->get('registration'));

        $lineItemsContainer = $this->client->listLineItems(
            $registration,
            $request->get('url'),
            $request->get('resourceId'),
            $request->get('resourceLinkId'),
            $request->get('tag'),
            (int)$request->get('limit')
        );

        return new Response(
            $this->twig->render(
                'tool/ajax/ags/listLineItems.html.twig',
                [
                    'registration' => $registration,
                    'lineItemsContainer' => $lineItemsContainer,
                    'lineItemsContainerUrl' => $request->get('url'),
                    'canWriteLineItem' => ScopePermissionVoter::canWriteLineItem(explode(',', $request->get('scopes'))),
                    'scopes' => $request->get('scopes')
                ]
            )
        );
    }
}
