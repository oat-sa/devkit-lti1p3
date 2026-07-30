<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
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
        $date = !empty($request->get('acsDate'))
            ? Carbon::createFromFormat('Y-m-d H:i', $request->get('acsDate'))
            : Carbon::now();

        $acsControl = new AcsControl(
            new LtiResourceLink($request->get('acsResourceLink')),
            $request->get('acsSub'),
            $request->get('acsAction'),
            $date,
            (int)$request->get('acsAttemptNumber') ?: 1,
            $request->get('acsIss'),
            $request->get('acsExtraTime') === '' ? null : (int)$request->get('acsExtraTime'),
            $request->get('acsSeverity') === '' ? null : (float)$request->get('acsSeverity'),
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
