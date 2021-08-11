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
            $membershipsList[$assessment->getIdentifier()] = [
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
