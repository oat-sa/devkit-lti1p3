<?php

/**
 * SPDX-FileCopyrightText: 2019-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Proctoring;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlInterface;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlResult;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlResultInterface;
use OAT\Library\Lti1p3Proctoring\Service\Server\Processor\AcsServiceServerControlProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AcsServiceServerControlProcessor implements AcsServiceServerControlProcessorInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var AssessmentRepository */
    private $repository;

    public function __construct(RequestStack $requestStack, AssessmentRepository $repository)
    {
        $this->requestStack = $requestStack;
        $this->repository = $repository;
    }

    public function process(RegistrationInterface $registration, AcsControlInterface $control): AcsControlResultInterface
    {
        $request = $this->requestStack->getCurrentRequest();
        $routeParameters = $request->attributes->get('_route_params');

        $assessmentIdentifier = $routeParameters['assessmentIdentifier'] ?? null;

        $assessment = $this->repository->find($assessmentIdentifier);

        if (null === $assessment) {
            throw new NotFoundHttpException(
                sprintf('Assessment with identifier %s cannot be found', $assessmentIdentifier)
            );
        }

        $assessment->addControl($control);

        $this->repository->save($assessment);

        return new AcsControlResult($assessment->getStatus(), $control->getExtraTime());
    }
}
