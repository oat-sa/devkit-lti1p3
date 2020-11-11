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

namespace App\Action\Platform\Service\Nrps;

use App\Nrps\MembershipServiceServerBuilder;
use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Service\LtiServiceSecurityToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class MembershipAction
{
    /** @var Security */
    private $security;

    /** @var MembershipServiceServerBuilder */
    private $builder;

    public function __construct(Security $security, MembershipServiceServerBuilder $builder)
    {
        $this->security = $security;
        $this->builder = $builder;
    }

    public function __invoke(Request $request): JsonResponse
    {
        /** @var LtiServiceSecurityToken $token */
        $token = $this->security->getToken();

        $membership = $this->builder->buildContextMembership(
            $token->getRegistration(),
            $request->get('role'),
            $request->get('limit'),
            $request->get('offset')
        );

        return new JsonResponse(
            $membership,
            Response::HTTP_OK,
            $membership->getRelationLink() ? ['Link' => $membership->getRelationLink()] : []
        );
    }
}
