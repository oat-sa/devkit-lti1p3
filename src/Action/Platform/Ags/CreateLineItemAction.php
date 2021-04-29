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

use App\Form\Platform\Ags\LineItemType;
use OAT\Library\Lti1p3Ags\Model\LineItem\LineItem;
use OAT\Library\Lti1p3Ags\Repository\LineItemRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class CreateLineItemAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var LineItemRepositoryInterface */
    private $repository;

    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        FlashBagInterface $flashBag,
        LineItemRepositoryInterface $repository,
        Environment $twig,
        FormFactoryInterface $factory,
        RouterInterface $router
    ) {
        $this->flashBag = $flashBag;
        $this->repository = $repository;
        $this->twig = $twig;
        $this->factory = $factory;
        $this->router = $router;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->factory->create(LineItemType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            $lineItem = new LineItem(
                 $formData['line_item_score_maximum'],
                 $formData['line_item_label'],
                 $formData['line_item_id']
            );

            $this->repository->save($lineItem);

            $this->flashBag->add('success', sprintf('Line item %s creation success', $formData['line_item_id']));

            return new RedirectResponse(
                $this->router->generate('platform_ags_view_line_item', ['lineItemIdentifier' => $formData['line_item_id']])
            );
        }

        return new Response(
            $this->twig->render(
                'platform/ags/createLineItem.html.twig',
                [
                    'form' => $form->createView(),
                ]
            )
        );
    }
}
