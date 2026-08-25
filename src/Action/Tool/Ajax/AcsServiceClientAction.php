<?php

// SPDX-FileCopyrightText: 2012-2026 Open Assessment Technologies S.A.
// Copyright (C) 2024 (original work) Open Assessment Technologies SA ;
//
// SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License

declare(strict_types=1);

namespace App\Action\Tool\Ajax;

use Carbon\Carbon;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLink;
use OAT\Library\Lti1p3Core\Service\Client\LtiServiceClientInterface;
use OAT\Library\Lti1p3Proctoring\Model\AcsControl;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlInterface;
use OAT\Library\Lti1p3Proctoring\Model\AcsControlResultInterface;
use OAT\Library\Lti1p3Proctoring\Serializer\AcsControlResultSerializer;
use OAT\Library\Lti1p3Proctoring\Service\AcsServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Twig\Environment;

class AcsServiceClientAction
{
    /** @var Environment */
    private $twig;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var LtiServiceClientInterface */
    private $client;

    public function __construct(
        Environment $twig,
        RegistrationRepositoryInterface $repository,
        LtiServiceClientInterface $client
    ) {
        $this->twig = $twig;
        $this->repository = $repository;
        $this->client = $client;
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
            (int) $request->get('acsAttemptNumber') ?: 1,
            $request->get('acsIss'),
            $request->get('acsExtraTime') === '' ? null : (int) $request->get('acsExtraTime'),
            $request->get('acsSeverity') === '' ? null : (float) $request->get('acsSeverity'),
            $request->get('acsReasonCode'),
            $request->get('acsReasonMessage')
        );

        $acsControlResult = $this->sendControl(
            $this->repository->find($request->get('registration')),
            $acsControl,
            $request->get('acsUrl'),
            $request
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

    /**
     * @throws LtiExceptionInterface
     */
    private function sendControl(
        RegistrationInterface $registration,
        AcsControlInterface $control,
        string $acsUrl,
        Request $request
    ): AcsControlResultInterface {
        try {
            if (null === $control->getIssuerIdentifier()) {
                $control->setIssuerIdentifier($registration->getPlatform()->getAudience());
            }

            $payload = $control->jsonSerialize();
            $payload = [...json_decode($request->request->get('acsExtraPayload') ?? '{}', true) ?: [], ...$payload];

            $response = $this->client->request(
                $registration,
                'POST',
                $acsUrl,
                [
                    'headers' => [
                        'Content-Type' => AcsServiceInterface::CONTENT_TYPE_CONTROL,
                    ],
                    'body' => json_encode($payload),
                ],
                [
                    AcsServiceInterface::AUTHORIZATION_SCOPE_CONTROL,
                ]
            );

            return (new AcsControlResultSerializer())->deserialize($response->getBody()->__toString());
        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot send ACS control: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}
