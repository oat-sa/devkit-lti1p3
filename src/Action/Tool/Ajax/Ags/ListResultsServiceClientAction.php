<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Tool\Ajax\Ags;

use OAT\Library\Lti1p3Ags\Service\LineItem\Client\LineItemServiceClient;
use OAT\Library\Lti1p3Ags\Service\Result\Client\ResultServiceClient;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ListResultsServiceClientAction
{
    /** @var Environment */
    private $twig;

    /** @var LineItemServiceClient */
    private $lineItemClient;

    /** @var ResultServiceClient */
    private $resultClient;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(
        Environment $twig,
        LineItemServiceClient $lineItemClient,
        ResultServiceClient $resultClient,
        RegistrationRepositoryInterface $repository
    ) {
        $this->twig = $twig;
        $this->lineItemClient = $lineItemClient;
        $this->resultClient = $resultClient;
        $this->repository = $repository;
    }

    public function __invoke(Request $request, string $lineItemIdentifier): Response
    {
        $registration = $this->repository->find($request->get('registration'));

        $lineItem = $this->lineItemClient->getLineItem($registration, $lineItemIdentifier);

        $results = $this->resultClient->listResults(
            $registration,
            $lineItemIdentifier,
            $request->get('user'),
            (int)$request->get('limit')
        );

        return new Response(
            $this->twig->render(
                'tool/ajax/ags/listResults.html.twig',
                [
                    'registration' => $registration,
                    'lineItem' => $lineItem,
                    'results' => array_values($results->getResults()->all()),
                    'mode' => $request->get('mode'),
                ]
            )
        );
    }
}
