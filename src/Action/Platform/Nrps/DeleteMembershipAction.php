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

namespace App\Action\Platform\Nrps;

use App\Nrps\MembershipRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

class DeleteMembershipAction
{
    /** @var RequestStack */
    private $requestStack;

    /** @var MembershipRepository */
    private $repository;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        RequestStack $requestStack,
        MembershipRepository $repository,
        RouterInterface $router
    ) {
        $this->requestStack = $requestStack;
        $this->repository = $repository;
        $this->router = $router;
    }

    public function __invoke(Request $request, string $membershipIdentifier): Response
    {
        $membership = $this->repository->find($membershipIdentifier);

        if (null === $membership) {
            throw new NotFoundHttpException(
                sprintf('Cannot find membership with id %s', $membershipIdentifier)
            );
        }

        try {
            $this->repository->delete($membership);

            $this->requestStack->getSession()->getFlashBag()->add('success', sprintf('Membership %s deletion success', $membershipIdentifier));
        } catch (Throwable $exception) {
            $this->requestStack->getSession()->getFlashBag()->add('error', $exception->getMessage());
        }

        return new RedirectResponse($this->router->generate('platform_nrps_list_memberships'));
    }
}
