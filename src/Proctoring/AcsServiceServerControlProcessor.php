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
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
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
