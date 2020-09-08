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

namespace App\Action\Tool\Ajax;

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Nrps\Service\Client\MembershipServiceClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class NrpsServiceClientAction
{
    /** @var Environment */
    private $twig;

    /** @var MembershipServiceClient */
    private $client;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(
        Environment $twig,
        MembershipServiceClient $client,
        RegistrationRepositoryInterface $repository
    ) {
        $this->twig = $twig;
        $this->client = $client;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $membership = $this->client->getContextMembership(
            $this->repository->find($request->get('registration')),
            $request->get('url'),
            $request->get('role'),
            intval($request->get('limit'))
        );

        return new Response(
            $this->twig->render('tool/ajax/nrps.html.twig', ['membership' => $membership])
        );
    }
}
