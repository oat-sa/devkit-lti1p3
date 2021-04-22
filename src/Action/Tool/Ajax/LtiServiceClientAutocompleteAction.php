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

namespace App\Action\Tool\Ajax;

use OAT\Library\Lti1p3BasicOutcome\Service\BasicOutcomeServiceInterface;
use OAT\Library\Lti1p3Nrps\Service\MembershipServiceInterface;
use OAT\Library\Lti1p3Proctoring\Service\AcsServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LtiServiceClientAutocompleteAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        switch ($request->get('type')) {
            case 'scope':
                $data = $this->getScopes($query);
                break;
            case 'media':
                $data = $this->getMediaTypes($query);
                break;
            default:
                $data = [];
                break;
        }

        return new JsonResponse($data);
    }

    private function getScopes(string $query): array
    {
        $scopes = [
            BasicOutcomeServiceInterface::AUTHORIZATION_SCOPE_BASIC_OUTCOME,
            MembershipServiceInterface::AUTHORIZATION_SCOPE_MEMBERSHIP,
            AcsServiceInterface::AUTHORIZATION_SCOPE_CONTROL,
        ];

        $filteredScopes = !empty($query)
            ? $this->filterData($query, $scopes)
            : $scopes;

        return array_values($filteredScopes);
    }

    private function getMediaTypes(string $query): array
    {
        $medias = [
            BasicOutcomeServiceInterface::CONTENT_TYPE_BASIC_OUTCOME,
            MembershipServiceInterface::CONTENT_TYPE_MEMBERSHIP,
            AcsServiceInterface::CONTENT_TYPE_CONTROL,
        ];

        $filteredMedias = !empty($query)
            ? $this->filterData($query, $medias)
            : $medias;

        return array_values($filteredMedias);
    }

    private function filterData(string $query, array $data): array
    {
        return array_filter(
            $data,
            static function (string $value) use ($query): bool {
                return false !== strpos($value, $query);
            }
        );
    }
}
