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
use App\Request\Encoder\Base64UrlEncoder;
use Carbon\Carbon;
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

            $lineItemIdentifierPath = $this->router->generate(
                'platform_service_ags_get_line_item',
                [
                    'contextIdentifier' => $formData['line_item_context_id'],
                    'lineItemIdentifier' => $formData['line_item_id'],
                ]
            );

            $lineItemIdentifier = sprintf('%s%s', rtrim($request->getSchemeAndHttpHost(), '/'), $lineItemIdentifierPath);

            $additionalProperties = json_decode($formData['line_item_additional_properties'] ?? '[]', true);

            $lineItem = new LineItem(
                $formData['line_item_score_maximum'],
                $formData['line_item_label'],
                $lineItemIdentifier,
                $formData['line_item_resource_id'],
                $formData['line_item_resource_link_id'],
                $formData['line_item_tag'],
                !empty($formData['line_item_start_date'])
                    ? Carbon::createFromFormat('Y-m-d H:i', $formData['line_item_start_date'])
                    : null,
                !empty($formData['line_item_end_date'])
                    ? Carbon::createFromFormat('Y-m-d H:i', $formData['line_item_end_date'])
                    : null,
                $additionalProperties
            );

            $this->repository->save($lineItem);

            $this->flashBag->add('success', sprintf('Line item %s creation success', $lineItemIdentifier));

            return new RedirectResponse(
                $this->router->generate('platform_ags_view_line_item', ['lineItemIdentifier' => Base64UrlEncoder::encode($lineItemIdentifier)])
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
