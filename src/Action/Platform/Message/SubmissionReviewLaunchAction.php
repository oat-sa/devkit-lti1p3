<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Platform\Message;

use App\Form\Generator\FormShareUrlGenerator;
use App\Form\Platform\Message\SubmissionReviewLaunchType;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ForUserClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3SubmissionReview\Message\Launch\Builder\SubmissionReviewLaunchRequestBuilder;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twig\Environment;

class SubmissionReviewLaunchAction
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

    /** @var SubmissionReviewLaunchRequestBuilder */
    private $builder;

    public function __construct(
        FlashBagInterface $flashBag,
        ParameterBagInterface $parameterBag,
        Environment $twig,
        FormFactoryInterface $factory,
        FormShareUrlGenerator $generator,
        SubmissionReviewLaunchRequestBuilder $builder
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
        $form = $this->factory->create(SubmissionReviewLaunchType::class);

        $form->handleRequest($request);

        $claims = [];
        $resourceLink = null;
        $submissionReviewLaunchRequest = null;

        if (!$form->isSubmitted()) {
            $form->setData($request->query->all());
        } elseif ($form->isValid()) {

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
                        'url' => $formData['submission_review_url'] ?? null,
                        'title' => $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['title'] ?? null,
                        'text' => $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['description'] ?? null,

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

            if ($formData['for_user_type'] === 'other') {
                $forUserClaim = new ForUserClaim(
                    $formData['for_user_id'] ?? Uuid::uuid4()->toString(),
                    $formData['for_user_name'] ?? null,
                    null,
                    null,
                    $formData['for_user_email'] ?? null,
                    null,
                    [
                        $formData['for_user_role'] ?? null
                    ]
                );
            } else {
                switch ($formData['user_type']) {
                    case 'list':
                        $selectedUser = $this->parameterBag->get('users')[$formData['user_list']];
                        $forUserClaim = new ForUserClaim(
                            $formData['user_list'],
                            $selectedUser['name'] ?? null,
                            $selectedUser['givenName'] ?? null,
                            $selectedUser['familyName'] ?? null,
                            $selectedUser['email'] ?? null
                        );
                        break;
                    case 'custom':
                        $forUserClaim = new ForUserClaim(
                            $loginHint['user_id'],
                            $loginHint['user_name'] ?? null,
                            null,
                            null,
                            $loginHint['user_email'] ?? null
                        );
                        break;
                    default:
                        $forUserClaim = new ForUserClaim(Uuid::uuid4()->toString());
                }
            }

            $agsClaim = new AgsClaim(
                $formData['ags_scopes'],
                null,
                $formData['ags_line_item_url']
            );

            if (null !== $resourceLink) {
                $submissionReviewLaunchRequest = $this->builder->buildLtiResourceLinkSubmissionReviewLaunchRequest(
                    $resourceLink,
                    $agsClaim,
                    $forUserClaim,
                    $formData['registration'],
                    json_encode($loginHint),
                    $formData['submission_review_url'] ?? null,
                    null,
                    [],
                    $claims
                );
            } else {
                $submissionReviewLaunchRequest = $this->builder->buildSubmissionReviewLaunchRequest(
                    $agsClaim,
                    $forUserClaim,
                    $formData['registration'],
                    json_encode($loginHint),
                    $formData['submission_review_url'] ?? null,
                    null,
                    [],
                    $claims
                );
            }

            $this->flashBag->add('success', 'LtiSubmissionReviewRequest generation success');
        }

        return new Response(
            $this->twig->render(
                'platform/message/submissionReviewLaunch.html.twig',
                [
                    'form' => $form->createView(),
                    'formSubmitted' => $form->isSubmitted(),
                    'formShareUrl' => $this->generator->generate('platform_message_launch_submission_review', $form),
                    'submissionReviewLaunchRequest' => $submissionReviewLaunchRequest,
                    'editorClaims' => $this->parameterBag->get('editor_claims')
                ]
            )
        );
    }
}
