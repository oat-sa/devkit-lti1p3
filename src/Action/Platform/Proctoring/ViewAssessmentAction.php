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

namespace App\Action\Platform\Proctoring;

use App\Proctoring\AssessmentRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Environment;

class ViewAssessmentAction
{
    /** @var AssessmentRepository */
    private $repository;

    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(
        AssessmentRepository $repository,
        Environment $twig,
        FormFactoryInterface $formFactory
    ) {
        $this->repository = $repository;
        $this->twig = $twig;
        $this->formFactory = $formFactory;
    }

    public function __invoke(Request $request, string $assessmentIdentifier): Response
    {

        $assessment = $this->repository->find($assessmentIdentifier);

        if (null === $assessment) {
            throw new NotFoundHttpException(
                sprintf('Cannot find assessment with id %s', $assessmentIdentifier)
            );
        }

        return new Response(
            $this->twig->render(
                'platform/proctoring/viewAssessment.html.twig',
                [
                    'assessment' => $assessment
                ]
            )
        );
    }
}
