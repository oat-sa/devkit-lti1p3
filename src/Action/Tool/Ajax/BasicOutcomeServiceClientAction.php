<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Tool\Ajax;

use OAT\Library\Lti1p3BasicOutcome\Message\BasicOutcomeMessageInterface;
use OAT\Library\Lti1p3BasicOutcome\Service\Client\BasicOutcomeServiceClient;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class BasicOutcomeServiceClientAction
{
    /** @var Environment */
    private $twig;

    /** @var BasicOutcomeServiceClient */
    private $client;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(
        Environment $twig,
        BasicOutcomeServiceClient $client,
        RegistrationRepositoryInterface $repository
    ) {
        $this->twig = $twig;
        $this->client = $client;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->get('operation')) {
            case BasicOutcomeMessageInterface::TYPE_READ_RESULT:
                $basicOutcomeResponse = $this->client->readResult(
                    $this->repository->find($request->get('registration')),
                    $request->get('url'),
                    $request->get('resultSourcedId')
                );
                break;
            case BasicOutcomeMessageInterface::TYPE_REPLACE_RESULT:
                $basicOutcomeResponse = $this->client->replaceResult(
                    $this->repository->find($request->get('registration')),
                    $request->get('url'),
                    $request->get('resultSourcedId'),
                    (float)$request->get('score'),
                    $request->get('language')
                );
                break;
            case BasicOutcomeMessageInterface::TYPE_DELETE_RESULT:
                $basicOutcomeResponse = $this->client->deleteResult(
                    $this->repository->find($request->get('registration')),
                    $request->get('url'),
                    $request->get('resultSourcedId')
                );
                break;
        }

        return new Response(
            $this->twig->render(
                'tool/ajax/basic-outcome.html.twig',
                [
                    'response' => $basicOutcomeResponse,
                ]
            )
        );
    }
}
