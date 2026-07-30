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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetAssessmentAction implements ApiActionInterface
{
    /** @var AssessmentRepository */
    private $repository;

    /** @var UrlGenerator */
    private $generator;

    public function __construct(AssessmentRepository $repository, UrlGenerator $generator)
    {
        $this->repository = $repository;
        $this->generator = $generator;
    }

    public static function getName(): string
    {
        return 'Get ACS assessment';
    }

    public function __invoke(Request $request, string $assessmentIdentifier): Response
    {
        $assessment = $this->repository->find($assessmentIdentifier);

        if (null === $assessment) {
            throw new NotFoundHttpException(
                sprintf('cannot find assessment with identifier %s', $assessmentIdentifier)
            );
        }

        return new JsonResponse(
            [
                'assessment' => $assessment,
                'acs_url' => $this->generator->generate(
                    'platform_service_acs',
                    [
                        'assessmentIdentifier' => $assessment->getIdentifier(),
                    ]
                )
            ]
        );
    }
}
