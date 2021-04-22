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

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3Proctoring\Model\AcsControl;
use OAT\Library\Lti1p3Proctoring\Service\Client\AcsServiceClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class AcsServiceClientAction
{
    /** @var Environment */
    private $twig;

    /** @var AcsServiceClient */
    private $client;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(
        Environment $twig,
        AcsServiceClient $client,
        RegistrationRepositoryInterface $repository
    ) {
        $this->twig = $twig;
        $this->client = $client;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $acsControl = new AcsControl(
            new LtiResourceLink($request->get('acsResourceLink')),
            $request->get('acsSub'),
            $request->get('acsAction'),
            Carbon::now(),
            (int)$request->get('acsAttemptNumber'),
            $request->get('acsIss'),
            (int)$request->get('acsExtraTime'),
            (float)$request->get('acsSeverity'),
            $request->get('acsReasonCode'),
            $request->get('acsReasonMessage')
        );

        $acsControlResult = $this->client->sendControl(
            $this->repository->find($request->get('registration')),
            $acsControl,
            $request->get('acsUrl')
        );

        return new Response(
            $this->twig->render(
                'tool/ajax/acs.html.twig',
                [
                    'controlResult' => $acsControlResult,
                ]
            )
        );
    }
}
