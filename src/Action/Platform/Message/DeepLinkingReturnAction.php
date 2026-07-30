<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Platform\Message;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiPlatformMessageSecurityToken;
use OAT\Library\Lti1p3DeepLinking\Factory\ResourceCollectionFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

class DeepLinkingReturnAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var ResourceCollectionFactoryInterface */
    private $factory;

    /** @var Environment */
    private $twig;

    /** @var Security */
    private $security;

    public function __construct(
        FlashBagInterface $flashBag,
        ResourceCollectionFactoryInterface $factory,
        Environment $twig,
        Security $security
    )
    {
        $this->flashBag = $flashBag;
        $this->factory = $factory;
        $this->twig = $twig;
        $this->security = $security;
    }

    public function __invoke(Request $request): Response
    {
        /** @var LtiPlatformMessageSecurityToken $token */
        $token = $this->security->getToken();

        $this->flashBag->add('success', $token->getPayload()->getDeepLinkingMessage());

        return new Response(
            $this->twig->render(
                'platform/message/deepLinkingReturn.html.twig',
                [
                    'token' => $this->security->getToken(),
                    'resources' => $this->factory->createFromClaim($token->getPayload()->getDeepLinkingContentItems())
                ]
            )
        );
    }
}
