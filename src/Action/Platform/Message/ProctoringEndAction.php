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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
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
