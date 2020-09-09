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

use App\Form\Platform\Message\LtiLinkBuilderType;
use OAT\Library\Lti1p3Core\Launch\Builder\LtiLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Launch\Builder\OidcLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLink;
use OAT\Library\Lti1p3Core\User\UserIdentity;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twig\Environment;

class LtiLinkAction
{
    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var LtiLaunchRequestBuilder */
    private $builder;

    /** @var OidcLaunchRequestBuilder */
    private $oidcBuilder;

    public function __construct(
        Environment $twig,
        FormFactoryInterface  $factory,
        ParameterBagInterface $parameterBag,
        LtiLaunchRequestBuilder $builder,
        OidcLaunchRequestBuilder $oidcBuilder
    ) {
        $this->twig = $twig;
        $this->factory = $factory;
        $this->parameterBag = $parameterBag;
        $this->builder = $builder;
        $this->oidcBuilder = $oidcBuilder;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->factory->create(LtiLinkBuilderType::class);

        $form->handleRequest($request);

        $user = null;
        $claims = [];
        $ltiLaunchRequest = null;
        $oidcLtiLaunchRequest = null;

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            $resourceLink = new ResourceLink(
                Uuid::uuid4()->toString(),
                $formData['launch_url'] ?? null
            );

            if ($formData['claims']) {
                $claims = json_decode($formData['claims'], true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException(sprintf('json_decode error: %s', json_last_error_msg()));
                }
            }

            if ($formData['user']) {

                $userData = $this->parameterBag->get('users')[$formData['user']] ?? [];

                $userIdentity = new UserIdentity(
                    $formData['user'],
                    $userData['name'],
                    $userData['email'],
                    $userData['givenName'],
                    $userData['familyName'],
                    $userData['middleName'],
                    $userData['locale'],
                    $userData['picture']
                );

                $ltiLaunchRequest = $this->builder->buildUserResourceLinkLtiLaunchRequest(
                    $resourceLink,
                    $formData['registration'],
                    $userIdentity,
                    null,
                    [],
                    $claims
                );
            } else {
                $ltiLaunchRequest = $this->builder->buildResourceLinkLtiLaunchRequest(
                    $resourceLink,
                    $formData['registration'],
                    null,
                    [],
                    $claims
                );
            }

            $oidcLtiLaunchRequest = $this->oidcBuilder->buildResourceLinkOidcLaunchRequest(
                $resourceLink,
                $formData['registration'],
                $formData['user'] ?? 'anonymous',
                null,
                [],
                $claims
            );
        }

        return new Response(
            $this->twig->render('platform/message/ltiLink.html.twig', [
                'form' => $form->createView(),
                'ltiLaunchRequest' => $ltiLaunchRequest,
                'oidcLtiLaunchRequest' => $oidcLtiLaunchRequest,
            ])
        );
    }
}
