<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Platform\Ags;

use App\Form\Platform\Ags\LineItemType;
use App\Request\Encoder\Base64UrlEncoder;
use Carbon\Carbon;
use OAT\Library\Lti1p3Ags\Repository\LineItemRepositoryInterface;
use OAT\Library\Lti1p3Core\Util\Collection\Collection;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class EditLineItemAction
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

    public function __invoke(Request $request, string $lineItemIdentifier): Response
    {
        $lineItem = $this->repository->find($lineItemIdentifier);

        if (null === $lineItem) {
            throw new NotFoundHttpException(
                sprintf('Cannot find line item with id %s', $lineItemIdentifier)
            );
        }

        $lineItemStartDate = null !== $lineItem->getStartDateTime()
            ? $lineItem->getStartDateTime()->format('Y-m-d H:i')
            : null;

        $lineItemEndDate = null !== $lineItem->getEndDateTime()
            ? $lineItem->getEndDateTime()->format('Y-m-d H:i')
            : null;

        if ($lineItem->getAdditionalProperties()->count() !== 0) {
            $lineItemAdditionalProperties = json_encode($lineItem->getAdditionalProperties());
        } else {
            $lineItemAdditionalProperties = '';
        }

        $form = $this->factory->create(
            LineItemType::class,
            [
                'line_item_id' => $lineItem->getIdentifier(),
                'line_item_label' => $lineItem->getLabel(),
                'line_item_score_maximum' => $lineItem->getScoreMaximum(),
                'line_item_resource_id' => $lineItem->getResourceIdentifier(),
                'line_item_resource_link_id' => $lineItem->getResourceLinkIdentifier(),
                'line_item_tag' => $lineItem->getTag(),
                'line_item_start_date' => $lineItemStartDate,
                'line_item_end_date' => $lineItemEndDate,
                'line_item_additional_properties' => $lineItemAdditionalProperties,
            ],
            [
                'edit' => true
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            $formStartDate = !empty($formData['line_item_start_date'])
                ? Carbon::createFromFormat('Y-m-d H:i', $formData['line_item_start_date'])
                : null;

            $formEndDate = !empty($formData['line_item_end_date'])
                ? Carbon::createFromFormat('Y-m-d H:i', $formData['line_item_end_date'])
                : null;

            $additionalProperties = json_decode($formData['line_item_additional_properties'] ?? '[]', true);

            $lineItem
                ->setLabel($formData['line_item_label'])
                ->setScoreMaximum($formData['line_item_score_maximum'])
                ->setResourceIdentifier($formData['line_item_resource_id'] ?? null)
                ->setResourceLinkIdentifier($formData['line_item_resource_link_id'] ?? null)
                ->setTag($formData['line_item_tag'] ?? null)
                ->setStartDateTime($formStartDate)
                ->setEndDateTime($formEndDate)
                ->setAdditionalProperties((new Collection)->add($additionalProperties));

            $this->repository->save($lineItem);

            $this->flashBag->add('success', sprintf('Line item %s edition success', $formData['line_item_id']));

            return new RedirectResponse(
                $this->router->generate('platform_ags_view_line_item', ['lineItemIdentifier' => Base64UrlEncoder::encode($formData['line_item_id'])])
            );
        }

        return new Response(
            $this->twig->render(
                'platform/ags/editLineItem.html.twig',
                [
                    'lineItem' => $lineItem,
                    'form' => $form->createView(),
                ]
            )
        );
    }
}
