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
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3DeepLinking\Message\Launch\Builder\DeepLinkingLaunchRequestBuilder;
use OAT\Library\Lti1p3DeepLinking\Settings\DeepLinkingSettings;
use Ramsey\Uuid\Uuid;

class LtiMessageBuilder
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var UrlGenerator */
    private $urlGenerator;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $ltiResourceLinkLaunchRequestBuilder;

    /** @var DeepLinkingLaunchRequestBuilder */
    private $deepLinkingLaunchRequestBuilder;

    public function __construct(
        RegistrationRepositoryInterface $repository,
        UrlGenerator $urlGenerator,
        LtiResourceLinkLaunchRequestBuilder $ltiResourceLinkLaunchRequestBuilder,
        DeepLinkingLaunchRequestBuilder $deepLinkingLaunchRequestBuilder
    ) {
        $this->repository = $repository;
        $this->urlGenerator = $urlGenerator;
        $this->ltiResourceLinkLaunchRequestBuilder = $ltiResourceLinkLaunchRequestBuilder;
        $this->deepLinkingLaunchRequestBuilder = $deepLinkingLaunchRequestBuilder;
    }

    /**
     * @throw  InvalidArgumentException
     */
    public function buildLtiResourceLinkRequest(array $parameters): LtiMessageInterface
    {
        $claims = $parameters['claims'] ?? [];

        if (isset($claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK])) {
            $resourceLink = new LtiResourceLink(
                $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['id'],
                [
                    'url' => $parameters['target_link_uri'] ?? null,
                    'title' => $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['title'] ?? null,
                    'text' => $claims[LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK]['description'] ?? null,
                ]
            );
        } else {
            $resourceLink = new LtiResourceLink(
                Uuid::uuid4()->toString(),
                [
                    'url' => $parameters['target_link_uri'] ?? null
                ]
            );
        }

        return $this->ltiResourceLinkLaunchRequestBuilder->buildLtiResourceLinkLaunchRequest(
            $resourceLink,
            $this->getRegistration($parameters),
            $this->getLoginHint($parameters),
            $parameters['deployment_id'] ?? null,
            [],
            $claims
        );
    }

    /**
     * @throw  InvalidArgumentException
     */
    public function buildLtiDeepLinkingRequest(array $parameters): LtiMessageInterface
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
            $parameters['deep_linking_launch_url'] ?? null,
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
}
