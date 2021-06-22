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

namespace App\Action\Platform\Ags;

use OAT\Library\Lti1p3Ags\Repository\LineItemRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

class DeleteLineItemAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var LineItemRepositoryInterface */
    private $repository;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        FlashBagInterface $flashBag,
        LineItemRepositoryInterface $repository,
        RouterInterface $router
    ) {
        $this->flashBag = $flashBag;
        $this->repository = $repository;
        $this->router = $router;
    }

    public function __invoke(Request $request, string $lineItemIdentifier): Response
    {
        $lineItem = $this->repository->find($lineItemIdentifier);

        if (null === $lineItem) {
            throw new NotFoundHttpException(
                sprintf('Cannot find line item with id %s', $lineItemIdentifier)
            );
        }

        try {
            $this->repository->delete($lineItemIdentifier);

            $this->flashBag->add('success', sprintf('Line item %s deletion success', $lineItemIdentifier));
        } catch (Throwable $exception) {
            $this->flashBag->add('error', $exception->getMessage());
        }

        return new RedirectResponse($this->router->generate('platform_ags_list_line_items'));
    }
}
