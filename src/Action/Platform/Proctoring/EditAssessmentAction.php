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

use App\Form\Platform\Proctoring\AssessmentType;
use App\Proctoring\AssessmentRepository;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class EditAssessmentAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var AssessmentRepository */
    private $repository;

    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var RouterInterface */
    private $router;

    public function __construct(
        FlashBagInterface $flashBag,
        AssessmentRepository $repository,
        Environment $twig,
        FormFactoryInterface $factory,
        RouterInterface $router
    ) {
        $this->flashBag = $flashBag;
        $this->repository = $repository;
        $this->twig = $twig;
        $this->factory = $factory;
        $this->router = $router;
    }

    public function __invoke(Request $request, string $assessmentIdentifier): Response
    {
        $assessment = $this->repository->find($assessmentIdentifier);

        if (null === $assessment) {
            throw new NotFoundHttpException(
                sprintf('Cannot find assessment with id %s', $assessmentIdentifier)
            );
        }

        $form = $this->factory->create(
            AssessmentType::class,
            [
                'assessment_id' => $assessment->getIdentifier(),
                'assessment_status' => $assessment->getStatus(),
            ],
            [
                'edit' => true
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            $assessment->setStatus($formData['assessment_status']);

            $this->repository->save($assessment);

            $this->flashBag->add('success', sprintf('Assessment %s edition success', $formData['assessment_id']));

            return new RedirectResponse(
                $this->router->generate('platform_proctoring_view_assessment', ['assessmentIdentifier' => $formData['assessment_id']])
            );
        }

        return new Response(
            $this->twig->render(
                'platform/proctoring/editAssessment.html.twig',
                [
                    'assessment' => $assessment,
                    'form' => $form->createView(),
                ]
            )
        );
    }
}
