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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Action\Tool\Message;

use OAT\Library\Lti1p3Core\Message\Payload\Claim\ProctoringVerifiedUserClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3DeepLinking\Factory\ResourceCollectionFactoryInterface;
use OAT\Library\Lti1p3Proctoring\Message\Launch\Builder\StartAssessmentLaunchRequestBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProctoringResponseAction
{
    /** @var ResourceCollectionFactoryInterface */
    private $factory;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var StartAssessmentLaunchRequestBuilder */
    private $builder;

    public function __construct(
        ResourceCollectionFactoryInterface $factory,
        ParameterBagInterface $parameterBag,
        RegistrationRepositoryInterface $repository,
        StartAssessmentLaunchRequestBuilder $builder
    ) {
        $this->factory = $factory;
        $this->parameterBag = $parameterBag;
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
            ]
        );

        return new Response($startAssessmentMessage->toHtmlRedirectForm());
    }
}
