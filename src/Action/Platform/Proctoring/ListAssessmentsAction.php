<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Platform\Proctoring;

use App\Proctoring\AssessmentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ListAssessmentsAction
{
    /** @var AssessmentRepository */
    private $repository;

    /** @var Environment */
    private $twig;

    public function __construct(AssessmentRepository $repository, Environment $twig)
    {
        $this->repository = $repository;
        $this->twig = $twig;
    }

    public function __invoke(Request $request): Response
    {
        return new Response(
            $this->twig->render(
                'platform/proctoring/listAssessments.html.twig',
                [
                    'assessments' => $this->repository->findAll()
                ]
            )
        );
    }
}
