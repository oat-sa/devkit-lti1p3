<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
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
        return 'Create NRPS membership';
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
