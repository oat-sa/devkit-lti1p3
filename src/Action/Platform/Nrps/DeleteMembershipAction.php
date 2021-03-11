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
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Throwable;
use Twig\Environment;

class DeleteMembershipAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var MembershipRepository */
    private $repository;

    /** @var Environment */
    private $twig;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        FlashBagInterface $flashBag,
        MembershipRepository $repository,
        Environment $twig,
        RouterInterface $router
    ) {
        $this->flashBag = $flashBag;
        $this->repository = $repository;
        $this->twig = $twig;
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

            $this->flashBag->add('success', sprintf('Membership %s deletion success', $membershipIdentifier));
        } catch (Throwable $exception) {
            $this->flashBag->add('error', $exception->getMessage());
        }

        return new RedirectResponse($this->router->generate('platform_nrps_list_memberships'));
    }
}
