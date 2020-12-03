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

use OAT\Library\Lti1p3BasicOutcome\Message\BasicOutcomeMessageInterface;
use OAT\Library\Lti1p3BasicOutcome\Service\Client\BasicOutcomeServiceClient;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class BasicOutcomeServiceClientAction
{
    /** @var Environment */
    private $twig;

    /** @var BasicOutcomeServiceClient */
    private $client;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(
        Environment $twig,
        BasicOutcomeServiceClient $client,
        RegistrationRepositoryInterface $repository
    ) {
        $this->twig = $twig;
        $this->client = $client;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->get('operation')) {
            case BasicOutcomeMessageInterface::TYPE_READ_RESULT:
                $basicOutcomeResponse = $this->client->readResult(
                    $this->repository->find($request->get('registration')),
                    $request->get('url'),
                    $request->get('resultSourcedId')
                );
                break;
            case BasicOutcomeMessageInterface::TYPE_REPLACE_RESULT:
                $basicOutcomeResponse = $this->client->replaceResult(
                    $this->repository->find($request->get('registration')),
                    $request->get('url'),
                    $request->get('resultSourcedId'),
                    (float)$request->get('score'),
                    $request->get('language')
                );
                break;
            case BasicOutcomeMessageInterface::TYPE_DELETE_RESULT:
                $basicOutcomeResponse = $this->client->deleteResult(
                    $this->repository->find($request->get('registration')),
                    $request->get('url'),
                    $request->get('resultSourcedId')
                );
                break;
        }

        return new Response(
            $this->twig->render(
                'tool/ajax/basic-outcome.html.twig',
                [
                    'response' => $basicOutcomeResponse,
                ]
            )
        );
    }
}
