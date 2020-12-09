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

namespace App\Action\Platform\Service\BasicOutcome;

use OAT\Library\Lti1p3BasicOutcome\Serializer\Request\BasicOutcomeRequestSerializerInterface;
use OAT\Library\Lti1p3BasicOutcome\Serializer\Response\BasicOutcomeResponseSerializerInterface;
use OAT\Library\Lti1p3BasicOutcome\Service\BasicOutcomeServiceInterface;
use OAT\Library\Lti1p3BasicOutcome\Service\Server\Handler\BasicOutcomeServiceServerHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResultAction
{
    /** @var BasicOutcomeServiceServerHandler */
    private $handler;

    /** @var BasicOutcomeRequestSerializerInterface */
    private $basicOutcomeRequestSerializer;

    /** @var BasicOutcomeResponseSerializerInterface */
    private $basicOutcomeResponseSerializer;

    public function __construct(
        BasicOutcomeServiceServerHandler $handler,
        BasicOutcomeRequestSerializerInterface $basicOutcomeRequestSerializer,
        BasicOutcomeResponseSerializerInterface $basicOutcomeResponseSerializer
    ) {
        $this->handler = $handler;
        $this->basicOutcomeRequestSerializer = $basicOutcomeRequestSerializer;
        $this->basicOutcomeResponseSerializer = $basicOutcomeResponseSerializer;
    }

    public function __invoke(Request $request): Response
    {
        $basicOutcomeResponse = $this->handler->handle(
            $this->basicOutcomeRequestSerializer->deserialize($request->getContent())
        );

        return new Response(
            $this->basicOutcomeResponseSerializer->serialize($basicOutcomeResponse),
            Response::HTTP_OK,
            [
                'Content-Type' => BasicOutcomeServiceInterface::CONTENT_TYPE_BASIC_OUTCOME
            ]
        );
    }
}
