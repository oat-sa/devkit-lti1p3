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

namespace App\Service\Platform\Message;

use App\Generator\UrlGenerator;
use InvalidArgumentException;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\AgsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ForUserClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLinkInterface;
use OAT\Library\Lti1p3DeepLinking\Message\Launch\Builder\DeepLinkingLaunchRequestBuilder;
use OAT\Library\Lti1p3DeepLinking\Settings\DeepLinkingSettings;
use OAT\Library\Lti1p3Proctoring\Message\Launch\Builder\StartProctoringLaunchRequestBuilder;
use OAT\Library\Lti1p3SubmissionReview\Message\Launch\Builder\SubmissionReviewLaunchRequestBuilder;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LtiMessageBuilder
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var UrlGenerator */
    private $urlGenerator;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $ltiResourceLinkLaunchRequestBuilder;

    /** @var DeepLinkingLaunchRequestBuilder */
    private $deepLinkingLaunchRequestBuilder;

    /** @var StartProctoringLaunchRequestBuilder */
    private $startProctoringLaunchRequestBuilder;

    /** @var SubmissionReviewLaunchRequestBuilder */
    private $submissionReviewLaunchRequestBuilder;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        UrlGenerator $urlGenerator,
        ParameterBagInterface $parameterBag,
        LtiResourceLinkLaunchRequestBuilder $ltiResourceLinkLaunchRequestBuilder,
        DeepLinkingLaunchRequestBuilder $deepLinkingLaunchRequestBuilder,
        StartProctoringLaunchRequestBuilder $startProctoringLaunchRequestBuilder,
        SubmissionReviewLaunchRequestBuilder $submissionReviewLaunchRequestBuilder
    ) {
        $this->repository = $repository;
        $this->urlGenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->ltiResourceLinkLaunchRequestBuilder = $ltiResourceLinkLaunchRequestBuilder;
        $this->deepLinkingLaunchRequestBuilder = $deepLinkingLaunchRequestBuilder;
        $this->startProctoringLaunchRequestBuilder = $startProctoringLaunchRequestBuilder;
        $this->submissionReviewLaunchRequestBuilder = $submissionReviewLaunchRequestBuilder;
    }

    /**
     * @throw  InvalidArgumentException
     */
    public function buildLtiResourceLinkRequestMessage(array $parameters): LtiMessageInterface
    {
        return $this->ltiResourceLinkLaunchRequestBuilder->buildLtiResourceLinkLaunchRequest(
            $this->getResourceLink($parameters),
            $this->getRegistration($parameters),
            $this->getLoginHint($parameters),
            $parameters['deployment_id'] ?? null,
            [],
            $parameters['claims'] ?? []
        );
    }

    /**
     * @throw  InvalidArgumentException
     */
    public function buildLtiDeepLinkingRequestMessage(array $parameters): LtiMessageInterface
    {
        $settings = $parameters['deep_linking_settings'] ?? [];

        $deepLinkSettings = new DeepLinkingSettings(
            $this->urlGenerator->generate('platform_message_deep_linking_return'),
            $settings['accept_types'] ?? ['ltiResourceLink'],
            $settings['accept_presentation_document_targets'] ?? ['windows'],
            $settings['accept_media_types'] ?? null,
            (bool) ($settings['accept_multiple'] ?? true),
            (bool) ($settings['auto_create'] ?? false),
            $settings['title'] ?? null,
            $settings['text'] ?? null
        );

        return $this->deepLinkingLaunchRequestBuilder->buildDeepLinkingLaunchRequest(
            $deepLinkSettings,
            $this->getRegistration($parameters),
            $this->getLoginHint($parameters),
            $parameters['target_link_uri'] ?? null,
            $parameters['deployment_id'] ?? null,
            [],
            $parameters['claims'] ?? []
        );
    }

    /**
     * @throw  InvalidArgumentException
     */
    public function buildLtiStartProctoringMessage(array $parameters): LtiMessageInterface
    {
        return $this->startProctoringLaunchRequestBuilder->buildStartProctoringLaunchRequest(
            $this->getResourceLink($parameters),
            $this->getRegistration($parameters),
            $parameters['proctoring_start_assessment_url'],
            $this->getLoginHint($parameters),
            (int) ($parameters['proctoring_attempt_number'] ?? 1),
            $parameters['deployment_id'] ?? null,
            [],
            $parameters['claims'] ?? []
        );
    }

    /**
     * @throw  InvalidArgumentException
     */
    public function buildLtiSubmissionReviewRequestMessage(array $parameters): LtiMessageInterface
    {
        $agsClaim = new AgsClaim(
            $parameters['ags_scopes'],
            null,
            $parameters['ags_line_item_url']
        );

        $user = $parameters['user'] ?? null;
        $submissionOwner = $parameters['submission_owner'] ?? null;

        if (empty($submissionOwner)) {
            if (empty($user)) {
                $forUserClaim = new ForUserClaim(Uuid::uuid4()->toString());
            } else {
                $forUserClaim = new ForUserClaim(
                    $user['id'] ?? Uuid::uuid4()->toString(),
                    $user['name'] ?? null,
                    null,
                    null,
                    $user['email'] ?? null,
                    null,
                    [
                        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner'
                    ]
                );
            }
        } else {
            $forUserClaim = new ForUserClaim(
                $submissionOwner['id'] ?? Uuid::uuid4()->toString(),
                $submissionOwner['name'] ?? null,
                null,
                null,
                $submissionOwner['email'] ?? null,
                null,
                $submissionOwner['roles'] ?? [
                    'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner'
                ]
            );
        }

        return $this->submissionReviewLaunchRequestBuilder->buildSubmissionReviewLaunchRequest(
            $agsClaim,
            $forUserClaim,
            $this->getRegistration($parameters),
            $this->getLoginHint($parameters),
            $parameters['target_link_uri'] ?? null,
            $parameters['deployment_id'] ?? null,
            [],
            $parameters['claims'] ?? []
        );
    }

    /**
     * @throw  InvalidArgumentException
     */
    private function getRegistration(array $parameters): RegistrationInterface
    {
        $registrationIdentifier = $parameters['registration'] ?? null;

        if (empty($registrationIdentifier)) {
            throw new InvalidArgumentException('Missing registration');
        }

        $registration = $this->repository->find($registrationIdentifier);

        if (null === $registration) {
            throw new InvalidArgumentException(sprintf('Invalid registration %s', $registrationIdentifier));
        }

        return $registration;
    }

    private function getLoginHint(array $parameters): string
    {
        $user = $parameters['user'] ?? null;

        if (empty($user)) {
            $loginHint = [
                'type' => 'anonymous'
            ];
        } else {
            $loginHint = [
                'type' => 'custom',
                'user_id' => $user['id'] ?? Uuid::uuid4()->toString(),
                'user_name' => $user['name'] ?? null,
                'user_email' => $user['email'] ?? null,
                'user_locale' => $user['locale'] ?? null,
            ];
        }

        return json_encode($loginHint);
    }

    private function getResourceLink(array $parameters): LtiResourceLinkInterface
    {
        $claims = $parameters['claims'] ?? [];

        if (isset($claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK])) {
            return new LtiResourceLink(
                $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['id'],
                [
                    'url' => $parameters['target_link_uri'] ?? null,
                    'title' => $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['title'] ?? null,
                    'text' => $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['description'] ?? null,
                ]
            );
        } else {
            return new LtiResourceLink(
                Uuid::uuid4()->toString(),
                [
                    'url' => $parameters['target_link_uri'] ?? null
                ]
            );
        }
    }
}
