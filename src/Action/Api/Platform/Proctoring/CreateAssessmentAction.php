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
use App\Proctoring\Assessment;
use App\Proctoring\AssessmentRepository;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlResultInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class CreateAssessmentAction implements ApiActionInterface
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
        return 'Create ACS assessment';
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new BadRequestHttpException(
                sprintf('invalid request: %s', json_last_error_msg())
            );
        }

        try {
            $assessment = new Assessment(
                $data['id'],
                $data['status'] ?? AcsControlResultInterface::STATUS_NONE
            );
        } catch (Throwable $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        if (null !== $this->repository->find($assessment->getIdentifier())) {
            throw new ConflictHttpException(
                sprintf('an assessment already exists with identifier %s', $assessment->getIdentifier())
            );
        }

        $this->repository->save($assessment);

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
