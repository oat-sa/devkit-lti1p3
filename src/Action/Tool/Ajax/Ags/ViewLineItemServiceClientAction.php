<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Tool\Ajax\Ags;

use Exception;
use OAT\Library\Lti1p3Ags\Service\LineItem\Client\LineItemServiceClient;
use OAT\Library\Lti1p3Ags\Voter\ScopePermissionVoter;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class ViewLineItemServiceClientAction
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

    public function __invoke(Request $request, string $lineItemIdentifier): JsonResponse
    {
        try {
            $mode = $request->get('mode');

            $registration = $this->repository->find($request->get('registration'));

            $lineItem = $this->client->getLineItem($registration, $lineItemIdentifier);

            $permissions = ScopePermissionVoter::getPermissions(explode(',', $request->get('scopes')));

            $mode = $request->get('mode');

            $actions = [];

            if ($permissions['canWriteScore'] ?? false) {
                $actions[] = 'prepare-score';
            }

            if ($permissions['canWriteLineItem'] ?? false) {
                $actions[] = 'edit';
                $actions[] = 'delete';
            }

            if ($permissions['canReadResult'] ?? false) {
                $actions[] = 'prepare-results';
            }

            return new JsonResponse(
                [
                    'title' => 'Line item details',
                    'body' => $this->twig->render(
                        'tool/ajax/ags/viewLineItem.html.twig',
                        [
                            'registration' => $registration,
                            'lineItem' => $lineItem,
                            'mode' => $mode

                        ]
                    ),
                    'actions' => $this->twig->render(
                        'tool/ajax/ags/actionsLineItem.html.twig',
                        [
                            'registration' => $registration,
                            'lineItem' => $lineItem,
                            'mode' => $mode,
                            'actions' => $actions,
                            'scopes' => $request->get('scopes')
                        ]
                    ),
                ]
            );
        } catch (Exception $exception) {
            return new JsonResponse(
                [
                    'title' => 'Line item details',
                    'flashes' => $this->twig->render(
                        'notification/flashes.html.twig',
                        [
                            'flashes' => [
                                'error' => [
                                    sprintf('Line item %s details error: %s', $lineItemIdentifier, $exception->getMessage())
                                ]
                            ]
                        ]
                    ),
                    'body' => '',
                    'actions' => ''
                ]
            );
        }
    }
}
