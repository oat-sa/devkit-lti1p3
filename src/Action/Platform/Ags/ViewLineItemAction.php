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
 * Copyright (c) 2021-2025 (original work) Open Assessment Technologies SA;
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
