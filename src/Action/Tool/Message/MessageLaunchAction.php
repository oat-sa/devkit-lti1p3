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

namespace App\Action\Tool\Message;

use OAT\Bundle\Lti1p3Bundle\Security\Authentication\Token\Message\LtiToolMessageSecurityToken;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

class MessageLaunchAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var Environment */
    private $twig;

    /** @var Security */
    private $security;

    public function __construct(
        FlashBagInterface $flashBag,
        ParameterBagInterface $parameterBag,
        Environment $twig,
        Security $security
    ) {
        $this->flashBag = $flashBag;
        $this->parameterBag = $parameterBag;
        $this->twig = $twig;
        $this->security = $security;
    }

    public function __invoke(Request $request): Response
    {
        /** @var LtiToolMessageSecurityToken $token */
        $token = $this->security->getToken();

        switch($token->getPayload()->getMessageType()) {
            case LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST:
                return $this->handleLtiResourceLinkRequest($token);
            case LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST:
                return $this->handleLtiDeepLinkingRequest($token);
            case LtiMessageInterface::LTI_MESSAGE_TYPE_START_PROCTORING:
                return $this->handleLtiStartProctoring($token);
            default:
                throw new RuntimeException(
                    sprintf('Unhandled LTI message-type %s', $token->getPayload()->getMessageType())
                );
        }
    }

    private function handleLtiResourceLinkRequest(LtiToolMessageSecurityToken $token): Response
    {
        $this->flashBag->add('success', 'Tool LtiResourceLinkRequest launch success');

        return new Response(
            $this->twig->render(
                'tool/message/ltiResourceLinkLaunch.html.twig',
                [
                    'token' => $token
                ]
            )
        );
    }

    private function handleLtiDeepLinkingRequest(LtiToolMessageSecurityToken $token): Response
    {
        $this->flashBag->add(
            'success',
            'Tool LtiDeepLinkingRequest launch success, please select item(s) to be returned to the platform'
        );

        return new Response(
            $this->twig->render(
                'tool/message/deepLinkingLaunch.html.twig',
                [
                    'token' => $token,
                    'resources' => $this->parameterBag->get('deeplinking_resources')
                ]
            )
        );
    }

    private function handleLtiStartProctoring(LtiToolMessageSecurityToken $token): Response
    {
        $this->flashBag->add('success', 'Tool LtiStartProctoring launch success');

        return new Response(
            $this->twig->render(
                'tool/message/proctoringLaunch.html.twig',
                [
                    'token' => $token,
                ]
            )
        );
    }
}
