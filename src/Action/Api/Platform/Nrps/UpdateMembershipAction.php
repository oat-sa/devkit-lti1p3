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
use App\Nrps\MembershipService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateMembershipAction implements ApiActionInterface
{
    /** @var MembershipService */
    private $service;

    /** @var UrlGenerator */
    private $generator;

    public function __construct(
        MembershipService $service,
        UrlGenerator $generator
    ) {
        $this->service = $service;
        $this->generator = $generator;
    }

    public static function getName(): string
    {
        return 'Update NRPS membership';
    }

    public function __invoke(Request $request, string $membershipIdentifier): Response
    {
        if ('default' === $membershipIdentifier) {
            throw new AccessDeniedHttpException('the membership with identifier default cannot be updated');
        }

        $membership = $this->service->findMembership($membershipIdentifier);

        if (null === $membership) {
            throw new NotFoundHttpException(
                sprintf('cannot find membership with identifier %s', $membershipIdentifier)
            );
        }

        $data = json_decode($request->getContent(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new BadRequestHttpException(
                sprintf('invalid request: %s', json_last_error_msg())
            );
        }

        $context = $membership->getContext();
        $context
            ->setIdentifier($data['context']['id'] ?? $context->getIdentifier())
            ->setLabel($data['context']['label'] ?? $context->getLabel())
            ->setTitle($data['context']['title'] ?? $context->getTitle());

        $membership->setContext($context);

        if (array_key_exists('members', $data)) {
            $this->service->updateMembership($membership, $data['members']);
        }

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
