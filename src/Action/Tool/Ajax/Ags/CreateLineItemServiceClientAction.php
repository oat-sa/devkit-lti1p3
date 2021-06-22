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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Action\Tool\Ajax\Ags;

use Carbon\Carbon;
use Exception;
use InvalidArgumentException;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItem;
use OAT\Library\Lti1p3Ags\Service\LineItem\Client\LineItemServiceClient;
use OAT\Library\Lti1p3Ags\Voter\ScopePermissionVoter;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class CreateLineItemServiceClientAction
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
        try {
            $registration = $this->repository->find($request->get('registration'));

            parse_str($request->get('form'), $formParameters);

            if (empty($formParameters['lineItemLabel'])) {
                throw new InvalidArgumentException('Missing required label');
            }

            if (empty($formParameters['lineItemScoreMaximum'])) {
                throw new InvalidArgumentException('Missing required score maximum');
            }

            $additionalProperties = json_decode($formParameters['lineItemAdditionalProperties'] ?? '[]', true);

            $lineItem = new LineItem(
                (float)$formParameters['lineItemScoreMaximum'],
                $formParameters['lineItemLabel'],
                null,
                $formParameters['lineItemResourceIdentifier'] ?? null,
                $formParameters['lineItemResourceLinkIdentifier'] ?? null,
                $formParameters['lineItemTag'] ?? null,
                !empty($formParameters['lineItemStartDateTime'])
                    ? Carbon::createFromFormat('Y-m-d H:i', $formParameters['lineItemStartDateTime'])
                    : null,
                !empty($formParameters['lineItemEndDateTime'])
                    ? Carbon::createFromFormat('Y-m-d H:i', $formParameters['lineItemEndDateTime'])
                    : null,
                $additionalProperties ?? []
            );

            $lineItem = $this->client->createLineItem($registration, $lineItem, $formParameters['lineItemContainerUrl']);

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
                                    sprintf('Line item %s creation success', $lineItem->getIdentifier())
                                ]
                            ]
                        ]
                    ),
                    'body' => $this->twig->render(
                        'tool/ajax/ags/viewLineItem.html.twig',
                        [
                            'registration' => $registration,
                            'lineItem' => $lineItem,
                            'mode' => $mode,
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
                    'title' => 'Line item creation',
                    'flashes' => $this->twig->render(
                        'notification/flashes.html.twig',
                        [
                            'flashes' => [
                                'error' => [
                                    sprintf('Line item creation error: %s', $exception->getMessage())
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
