<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Platform\Message;

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Proctoring\Message\Launch\Builder\EndAssessmentLaunchRequestBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class ProctoringEndAction
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var EndAssessmentLaunchRequestBuilder */
    private $builder;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        EndAssessmentLaunchRequestBuilder $builder
    ) {
        $this->repository = $repository;
        $this->builder = $builder;
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $registration = $this->repository->find($request->get('registration'));

        $loginHint = [
            'type' => 'custom',
            'user_id' => $request->get('verified-user-name'),
            'user_name' => $request->get('verified-user-name'),
        ];

        if ($request->get('with-error') === 'on') {
            $endAssessmentMessage = $this->builder->buildEndAssessmentLaunchErrorRequest(
                $registration,
                json_encode($loginHint),
                $request->get('error-message'),
                $request->get('error-log'),
                $registration->getTool()->getLaunchUrl(),
                (int)$request->get('attempt-number')
            );
        } else {
            $endAssessmentMessage = $this->builder->buildEndAssessmentLaunchRequest(
                $registration,
                json_encode($loginHint),
                $registration->getTool()->getLaunchUrl(),
                (int)$request->get('attempt-number')
            );
        }

        return new RedirectResponse($endAssessmentMessage->toUrl());
    }
}
