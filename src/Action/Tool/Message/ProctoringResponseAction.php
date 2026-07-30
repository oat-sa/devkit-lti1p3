<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Tool\Message;

use OAT\Library\Lti1p3Core\Message\Payload\Claim\ProctoringVerifiedUserClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Proctoring\Message\Launch\Builder\StartAssessmentLaunchRequestBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProctoringResponseAction
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var StartAssessmentLaunchRequestBuilder */
    private $builder;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        StartAssessmentLaunchRequestBuilder $builder
    ) {
        $this->repository = $repository;
        $this->builder = $builder;
    }

    public function __invoke(Request $request): Response
    {
        $registration = $this->repository->find($request->get('registration'));

        $startAssessmentMessage = $this->builder->buildStartAssessmentLaunchRequest(
            new ResourceLinkClaim($request->get('resource-link-id')),
            $registration,
            $request->get('start-assessment-url'),
            $request->get('session-data'),
            (int)$request->get('attempt-number'),
            null,
            [
                new ProctoringVerifiedUserClaim(
                    [
                        'name' => $request->get('verified-user-name')
                    ]
                )
            ],
            $request->get('end-assessment-return') === 'on'
        );

        return new Response($startAssessmentMessage->toHtmlRedirectForm());
    }
}
