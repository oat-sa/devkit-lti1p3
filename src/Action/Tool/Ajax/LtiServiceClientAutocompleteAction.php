<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Tool\Ajax;

use OAT\Library\Lti1p3Ags\Service\LineItem\LineItemServiceInterface;
use OAT\Library\Lti1p3Ags\Service\Result\ResultServiceInterface;
use OAT\Library\Lti1p3Ags\Service\Score\ScoreServiceInterface;
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
            LineItemServiceInterface::AUTHORIZATION_SCOPE_LINE_ITEM,
            LineItemServiceInterface::AUTHORIZATION_SCOPE_LINE_ITEM_READ_ONLY,
            ScoreServiceInterface::AUTHORIZATION_SCOPE_SCORE,
            ResultServiceInterface::AUTHORIZATION_SCOPE_RESULT_READ_ONLY,
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
            LineItemServiceInterface::CONTENT_TYPE_LINE_ITEM,
            LineItemServiceInterface::CONTENT_TYPE_LINE_ITEM_CONTAINER,
            ScoreServiceInterface::CONTENT_TYPE_SCORE,
            ResultServiceInterface::CONTENT_TYPE_RESULT_CONTAINER,
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
