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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Action\Api\Platform\Nrps;

use App\Action\Api\ApiActionInterface;
use App\Nrps\MembershipRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteMembershipAction implements ApiActionInterface
{
    /** @var MembershipRepository */
    private $repository;

    public function __construct(MembershipRepository $repository)
    {
        $this->repository = $repository;
    }

    public static function getName(): string
    {
        return 'NRPS membership deletion';
    }

    public function __invoke(Request $request, string $membershipIdentifier): Response
    {
        if ('default' === $membershipIdentifier) {
            throw new AccessDeniedHttpException('the membership with identifier default cannot be deleted');
        }

        $membership = $this->repository->find($membershipIdentifier);

        if (null === $membership) {
            throw new NotFoundHttpException(
                sprintf('cannot find membership with identifier %s', $membershipIdentifier)
            );
        }

        $this->repository->delete($membership);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
