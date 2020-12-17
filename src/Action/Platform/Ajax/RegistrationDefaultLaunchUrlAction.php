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

namespace App\Action\Platform\Ajax;

use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RegistrationDefaultLaunchUrlAction
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(RegistrationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $registration = $this->repository->find($request->get('registration'));

        switch ($request->get('type')) {
            case LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST:
                $url = $registration->getTool()->getLaunchUrl();
                break;
            case LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST:
                $url = $registration->getTool()->getDeepLinkingUrl();
                break;
            default:
                $url = null;
        }

        return new JsonResponse(['url' => $url]);
    }
}
