<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Tool\Ajax\Ags;

use Carbon\Carbon;
use Exception;
use InvalidArgumentException;
use OAT\Library\Lti1p3Ags\Model\Score\Score;
use OAT\Library\Lti1p3Ags\Service\LineItem\Client\LineItemServiceClient;
use OAT\Library\Lti1p3Ags\Service\Score\Client\ScoreServiceClient;
use OAT\Library\Lti1p3Ags\Voter\ScopePermissionVoter;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PublishScoreServiceClientAction
{
    /** @var Environment */
    private $twig;

    /** @var LineItemServiceClient */
    private $lineItemServiceClient;

    /** @var ScoreServiceClient */
    private $scoreServiceClient;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(
        Environment $twig,
        LineItemServiceClient $lineItemServiceClient,
        ScoreServiceClient $scoreServiceClient,
        RegistrationRepositoryInterface $repository
    ) {
        $this->twig = $twig;
        $this->lineItemServiceClient = $lineItemServiceClient;
        $this->scoreServiceClient = $scoreServiceClient;
        $this->repository = $repository;
    }

    public function __invoke(Request $request, string $lineItemIdentifier): Response
    {
        try {
            $registration = $this->repository->find($request->get('registration'));

            $lineItem = $this->lineItemServiceClient->getLineItem($registration, $lineItemIdentifier);

            parse_str($request->get('form'), $formParameters);

            if (empty($formParameters['scoreUserIdentifier'])) {
                throw new InvalidArgumentException('Missing required user identifier');
            }

            $additionalProperties = json_decode($formParameters['scoreAdditionalProperties'] ?? '[]', true);

            $score = new Score(
                $formParameters['scoreUserIdentifier'],
                $formParameters['scoreActivityProgress'],
                $formParameters['scoreGradingProgress'],
                $lineItem->getIdentifier(),
                (float)$formParameters['scoreGiven'] ?? null,
                (float)$formParameters['scoreMaximum'] ?? null,
                $formParameters['scoreComment'] ?? null,
                !empty($formParameters['scoreTimestamp'])
                    ? Carbon::createFromFormat('Y-m-d H:i', $formParameters['scoreTimestamp'])
                    : Carbon::now(),
                $additionalProperties ?? []
            );

            $this->scoreServiceClient->publishScore($registration, $score, $lineItemIdentifier);

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
                    'flashes' => $this->twig->render(
                        'notification/flashes.html.twig',
                        [
                            'flashes' => [
                                'success' => [
                                    sprintf('Score publication on line item %s success', $lineItemIdentifier)
                                ]
                            ]
                        ]
                    ),
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
                    'title' => 'Line item score publication',
                    'flashes' => $this->twig->render(
                        'notification/flashes.html.twig',
                        [
                            'flashes' => [
                                'error' => [
                                    sprintf('Score publication error: %s', $exception->getMessage())
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
