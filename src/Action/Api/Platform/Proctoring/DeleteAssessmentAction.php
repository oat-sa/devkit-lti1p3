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
use App\Proctoring\AssessmentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteAssessmentAction implements ApiActionInterface
{
    /** @var AssessmentRepository */
    private $repository;

    public function __construct(AssessmentRepository $repository)
    {
        $this->repository = $repository;
    }

    public static function getName(): string
    {
        return 'Delete ACS assessment';
    }

    public function __invoke(Request $request, string $assessmentIdentifier): Response
    {
        $assessment = $this->repository->find($assessmentIdentifier);

        if (null === $assessment) {
            throw new NotFoundHttpException(
                sprintf('cannot find assessment with identifier %s', $assessmentIdentifier)
            );
        }

        $this->repository->delete($assessment);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
