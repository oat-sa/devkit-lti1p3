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
