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

namespace App\Action\Platform\Message;

use App\Form\Generator\FormShareUrlGenerator;
use App\Form\Platform\Message\ProctoringLaunchType;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3Proctoring\Message\Launch\Builder\StartProctoringLaunchRequestBuilder;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twig\Environment;

class ProctoringLaunchAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var FormShareUrlGenerator */
    private $generator;

    /** @var StartProctoringLaunchRequestBuilder */
    private $builder;

    public function __construct(
        FlashBagInterface $flashBag,
        ParameterBagInterface $parameterBag,
        Environment $twig,
        FormFactoryInterface $factory,
        FormShareUrlGenerator $generator,
        StartProctoringLaunchRequestBuilder $builder
    ) {
        $this->flashBag = $flashBag;
        $this->parameterBag = $parameterBag;
        $this->twig = $twig;
        $this->factory = $factory;
        $this->generator = $generator;
        $this->builder = $builder;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->factory->create(ProctoringLaunchType::class);

        $form->handleRequest($request);

        $user = null;
        $claims = [];
        $startProctoringLaunchRequest = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            if ($formData['claims']) {
                $claims = json_decode($formData['claims'], true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException(sprintf('json_decode error: %s', json_last_error_msg()));
                }
            }

            if (isset($claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK])) {
                $resourceLink = new LtiResourceLink(
                    $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['id'],
                    [
                        'url' => $formData['start_proctoring_url'] ?? null,
                        'title' => $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['title'] ?? null,
                        'text' => $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['description'] ?? null,

                    ]
                );
            } else {
                $resourceLink = new LtiResourceLink(
                    Uuid::uuid4()->toString(),
                    [
                        'url' => $formData['start_proctoring_url'] ?? null
                    ]
                );
            }

            switch ($formData['user_type']) {
                case 'list':
                    $loginHint = [
                        'type' => 'list',
                        'user_id' => $formData['user_list']
                    ];
                    break;
                case 'custom':
                    $loginHint = [
                        'type' => 'custom',
                        'user_id' => $formData['custom_user_id'] ?? Uuid::uuid4()->toString(),
                        'user_name' => $formData['custom_user_name'],
                        'user_email' => $formData['custom_user_email'],
                        'user_locale' => $formData['custom_user_locale'],
                    ];
                    break;
                default:
                    $loginHint = [
                        'type' => 'anonymous'
                    ];
            }

            $startProctoringLaunchRequest = $this->builder->buildStartProctoringLaunchRequest(
                $resourceLink,
                $formData['registration'],
                $formData['start_assessment_url'],
                json_encode($loginHint),
                $formData['attempt_number'],
                null,
                [],
                $claims
            );

            $this->flashBag->add('success', 'LtiStartProctoring generation success');
        } else {
            $form->setData($request->query->all());
        }

        return new Response(
            $this->twig->render(
                'platform/message/proctoringLaunch.html.twig',
                [
                    'form' => $form->createView(),
                    'formSubmitted' => $form->isSubmitted(),
                    'formShareUrl' => $this->generator->generate('platform_message_launch_proctoring', $form),
                    'startProctoringLaunchRequest' => $startProctoringLaunchRequest,
                    'editorClaims' => $this->parameterBag->get('editor_claims')
                ]
            )
        );
    }
}
