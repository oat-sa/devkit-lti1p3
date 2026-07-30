<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Platform\Ags;

use App\Ags\ScoreRepository;
use OAT\Library\Lti1p3Ags\Repository\LineItemRepositoryInterface;
use OAT\Library\Lti1p3Ags\Repository\ResultRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

readonly class ViewLineItemAction
{
    /** @var LineItemRepositoryInterface */
    public function __construct(
        private LineItemRepositoryInterface $lineItemRepository,
        private ScoreRepository $scoreRepository,
        private ResultRepositoryInterface $resultRepository,
        private Environment $twig,
    ) {
    }

    public function __invoke(Request $request, string $lineItemIdentifier): Response
    {
        $lineItem = $this->lineItemRepository->find($lineItemIdentifier);

        if (null === $lineItem) {
            throw new NotFoundHttpException(
                sprintf('Cannot find line item with id %s', $lineItemIdentifier)
            );
        }

        $scores = $this->scoreRepository->findCollectionByLineItemIdentifier($lineItemIdentifier);
        $results = $this->resultRepository->findCollectionByLineItemIdentifier($lineItemIdentifier);

        return new Response(
            $this->twig->render(
                'platform/ags/viewLineItem.html.twig',
                [
                    'lineItem' => $lineItem,
                    'scores' => array_values($scores->all()),
                    'results' => array_values($results->all())
                ]
            )
        );
    }
}
