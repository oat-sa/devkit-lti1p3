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

namespace App\Action\Api\Platform\Message;

use App\Action\Api\ApiActionInterface;
use App\Service\Platform\Message\LtiMessageBuilder;
use InvalidArgumentException;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class CreateMessageLaunchAction implements ApiActionInterface
{
    /** @var LtiMessageBuilder */
    private $builder;

    public function __construct(LtiMessageBuilder $builder)
    {
        $this->builder = $builder;
    }

    public static function getName(): string
    {
        return 'Platform LTI message creation';
    }

    public function __invoke(Request $request, string $messageType): JsonResponse
    {
        $parameters = json_decode($request->getContent(), true);
        $verbose = (bool) $request->get('verbose', false);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new BadRequestHttpException(
                sprintf('Invalid json, json_decode error: %s', json_last_error_msg())
            );
        }

        switch (ucfirst($messageType)) {
            case LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST:
                $message = $this->builder->buildLtiResourceLinkRequest($parameters);
                break;
            default:
                throw new BadRequestHttpException(
                    sprintf('Invalid message type %s', $messageType)
                );
        }

        $payload = [
            'link' => $message->toUrl()
        ];

        if ($verbose) {
            $payload['details'] = [
                'url' => $message->getUrl(),
                'parameters' => $message->getParameters(),
            ];
        }

        return new JsonResponse($payload);
    }
}
