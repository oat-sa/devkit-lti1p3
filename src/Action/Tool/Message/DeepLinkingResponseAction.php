<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Tool\Message;

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3DeepLinking\Factory\ResourceCollectionFactoryInterface;
use OAT\Library\Lti1p3DeepLinking\Message\Launch\Builder\DeepLinkingLaunchResponseBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeepLinkingResponseAction
{
    /** @var ResourceCollectionFactoryInterface */
    private $factory;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var DeepLinkingLaunchResponseBuilder */
    private $builder;

    public function __construct(
        ResourceCollectionFactoryInterface $factory,
        ParameterBagInterface $parameterBag,
        RegistrationRepositoryInterface $repository,
        DeepLinkingLaunchResponseBuilder $builder
    ) {
        $this->factory = $factory;
        $this->parameterBag = $parameterBag;
        $this->repository = $repository;
        $this->builder = $builder;
    }

    public function __invoke(Request $request): Response
    {
        $registration = $this->repository->find($request->get('registration'));

        $availableResources = $this->parameterBag->get('deeplinking_resources');
        $selectedResources = [];

        foreach ($request->get('selected-resources', []) as $resourceIdentifier) {
            $selectedResources[] = $availableResources[$resourceIdentifier];
        }

        $resourceCollection = $this->factory->create($selectedResources);

        $deepLinkingResponse = $this->builder->buildDeepLinkingLaunchResponse(
            $resourceCollection,
            $registration,
            $request->get('deep-linking-return-url'),
            null,
            $request->get('deep-linking-data')
        );

        /*$deepLinkingResponse = $this->builder->buildLaunchErrorResponse(
            $registration,
            $request->get('deep-linking-return-url'),
            null,
            $request->get('deep-linking-data'),
            'error message',
            'error log'
        );*/

        return new Response($deepLinkingResponse->toHtmlRedirectForm());
    }
}
