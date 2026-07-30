<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Api\Platform\Proctoring;

use App\Action\Api\ApiActionInterface;
use App\Generator\UrlGenerator;
use App\Proctoring\AssessmentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListAssessmentsAction implements ApiActionInterface
{
    /** @var AssessmentRepository */
    private $repository;

    /** @var UrlGenerator */
    private $generator;

    public function __construct(
        AssessmentRepository $repository,
        UrlGenerator $generator
    ) {
        $this->repository = $repository;
        $this->generator = $generator;
    }

    public static function getName(): string
    {
        return 'List ACS assessments';
    }

    public function __invoke(Request $request): Response
    {
        $limit = $request->query->has('limit') ? intval($request->query->get('limit')) : null;
        $offset = $request->query->has('offset') ? intval($request->query->get('offset')) : null;

        $assessments = $this->repository->findAll();

        $assessments = array_slice($assessments, $offset ?: 0, $limit);

        $membershipsList = [];

        foreach ($assessments as $assessment) {
            $membershipsList[] = [
                'assessment' => $assessment,
                'acs_url' => $this->generator->generate(
                    'platform_service_acs',
                    [
                        'assessmentIdentifier' => $assessment->getIdentifier(),
                    ]
                )
            ];
        }

        return new JsonResponse(
            [
                'assessments' => $membershipsList
            ]
        );
    }
}
