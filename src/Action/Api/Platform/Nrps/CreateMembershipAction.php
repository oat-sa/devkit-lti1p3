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

namespace App\Action\Api\Platform\Nrps;

use App\Action\Api\ApiActionInterface;
use App\Generator\UrlGenerator;
use App\Nrps\MembershipRepository;
use OAT\Library\Lti1p3Nrps\Serializer\MembershipSerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class CreateMembershipAction implements ApiActionInterface
{
    /** @var MembershipSerializerInterface */
    private $serializer;

    /** @var MembershipRepository */
    private $repository;

    /** @var UrlGenerator */
    private $generator;

    public function __construct(
        MembershipSerializerInterface $serializer,
        MembershipRepository $repository,
        UrlGenerator $generator
    ) {
        $this->serializer = $serializer;
        $this->repository = $repository;
        $this->generator = $generator;
    }

    public static function getName(): string
    {
        return 'NRPS membership creation';
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $membership = $this->serializer->deserialize($request->getContent());
        } catch (Throwable $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        if (null !== $this->repository->find($membership->getIdentifier()) || 'default' === $membership->getIdentifier()) {
            throw new ConflictHttpException(
                sprintf('a membership already exists with identifier %s', $membership->getIdentifier())
            );
        }

        $this->repository->save($membership);

        return new JsonResponse(
            [
                'membership' => $membership,
                'nrps_url' => $this->generator->generate(
                    'platform_service_nrps',
                    [
                        'contextIdentifier' => $membership->getContext()->getIdentifier(),
                        'membershipIdentifier' => $membership->getIdentifier(),
                    ]
                )
            ]
        );
    }
}
