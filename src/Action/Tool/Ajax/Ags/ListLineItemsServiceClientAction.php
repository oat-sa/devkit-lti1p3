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
