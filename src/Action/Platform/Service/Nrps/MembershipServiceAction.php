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
use OAT\Library\Lti1p3Nrps\Service\MembershipServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class MembershipServiceAction
{
    /** @var MembershipServiceServerBuilder */
    private $builder;

    public function __construct(MembershipServiceServerBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function __invoke(Request $request, string $contextIdentifier, string $membershipIdentifier): JsonResponse
    {
        if (false === strpos($request->headers->get('Accept', ''), MembershipServiceInterface::CONTENT_TYPE_MEMBERSHIP)) {
            throw new NotAcceptableHttpException(
                sprintf('Not acceptable content type, accepts: %s', MembershipServiceInterface::CONTENT_TYPE_MEMBERSHIP)
            );
        }

        $limit = $request->get('limit');
        $offset = $request->get('offset');

        $membership = $this->builder->buildContextMembership(
            $request->get('role'),
            $limit ? intval($limit) : null,
            $offset ? intval($offset) : null
        );

        $responseHeaders = [
            'Content-Type' => MembershipServiceInterface::CONTENT_TYPE_MEMBERSHIP
        ];

        if ($membership->getRelationLink()) {
            $responseHeaders['Link'] = $membership->getRelationLink();
        }

        return new JsonResponse($membership, Response::HTTP_OK, $responseHeaders);
    }
}
