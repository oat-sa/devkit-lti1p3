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

use InvalidArgumentException;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\LtiResourceLinkLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use Ramsey\Uuid\Uuid;

class LtiMessageBuilder
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var LtiResourceLinkLaunchRequestBuilder */
    private $builder;

    public function __construct(RegistrationRepositoryInterface $repository, LtiResourceLinkLaunchRequestBuilder $builder)
    {
        $this->repository = $repository;
        $this->builder = $builder;
    }

    public function buildLtiResourceLinkRequest(array $parameters): LtiMessageInterface
    {
        $registrationIdentifier = $parameters['registration'] ?? null;

        if (empty($registrationIdentifier)) {
            throw new InvalidArgumentException('Missing registration');
        }

        $registration = $this->repository->find($registrationIdentifier);

        if (null === $registration) {
            throw new InvalidArgumentException(sprintf('Invalid registration %s', $registrationIdentifier));
        }

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

        return $this->builder->buildLtiResourceLinkLaunchRequest(
            $resourceLink,
            $registration,
            json_encode($loginHint),
            $parameters['deployment_id'] ?? null,
            [],
            $claims
        );
    }
}
