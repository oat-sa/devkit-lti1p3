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

use App\Form\Generator\FormShareUrlGenerator;
use App\Form\Platform\Message\DeepLinkingLaunchType;
use App\Generator\UrlGenerator;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3DeepLinking\Message\Launch\Builder\DeepLinkingLaunchRequestBuilder;
use OAT\Library\Lti1p3DeepLinking\Settings\DeepLinkingSettings;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twig\Environment;

class DeepLinkingLaunchAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var ParameterBagInterface */
    private $parameterBag;

    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var FormShareUrlGenerator */
    private $formShareUrlGenerator;

    /** @var UrlGenerator */
    private $urlGenerator;

    /** @var DeepLinkingLaunchRequestBuilder */
    private $builder;

    public function __construct(
        FlashBagInterface $flashBag,
        ParameterBagInterface $parameterBag,
        Environment $twig,
        FormFactoryInterface $factory,
        FormShareUrlGenerator $formShareUrlGenerator,
        UrlGenerator $urlGenerator,
        DeepLinkingLaunchRequestBuilder $builder
    ) {
        $this->flashBag = $flashBag;
        $this->parameterBag = $parameterBag;
        $this->twig = $twig;
        $this->factory = $factory;
        $this->formShareUrlGenerator = $formShareUrlGenerator;
        $this->urlGenerator = $urlGenerator;
        $this->builder = $builder;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->factory->create(DeepLinkingLaunchType::class);

        $form->handleRequest($request);

        $claims = [];
        $deepLinkingLaunchRequest = null;

        if (!$form->isSubmitted()) {
            $form->setData($request->query->all());
        } elseif ($form->isValid()) {

            $formData = $form->getData();

            $deepLinkSettings = new DeepLinkingSettings(
                $this->urlGenerator->generate('platform_message_deep_linking_return'),
                $formData['accept_types'],
                $formData['accept_presentation_document_targets'],
                $formData['accept_media_types'],
                $formData['accept_multiple'],
                $formData['auto_create']
            );

            if ($formData['claims']) {
                $claims = json_decode($formData['claims'], true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException(sprintf('json_decode error: %s', json_last_error_msg()));
                }
            }

            switch ($formData['user_type']) {
                case 'list':
                    $loginHint = [
                        'type' => 'list',
                        'user_id' => $formData['user_list']
                    ];
                    break;
                case 'custom':
                    $loginHint = [
                        'type' => 'custom',
                        'user_id' => $formData['custom_user_id'] ?? Uuid::uuid4()->toString(),
                        'user_name' => $formData['custom_user_name'],
                        'user_email' => $formData['custom_user_email'],
                        'user_locale' => $formData['custom_user_locale'],
                    ];
                    break;
                default:
                    $loginHint = [
                        'type' => 'anonymous'
                    ];
            }

            /** @var RegistrationInterface $registration */
            $registration = $formData['registration'];

            $deepLinkingLaunchRequest = $this->builder->buildDeepLinkingLaunchRequest(
                $deepLinkSettings,
                $registration,
                json_encode($loginHint),
                $formData['deep_linking_url'] ?? $registration->getTool()->getDeepLinkingUrl(),
                null,
                [],
                $claims
            );

            $this->flashBag->add('success', 'LtiDeepLinkingRequest generation success');
        }

        return new Response(
            $this->twig->render(
                'platform/message/deepLinkingLaunch.html.twig',
                [
                    'form' => $form->createView(),
                    'formSubmitted' => $form->isSubmitted(),
                    'formShareUrl' => $this->formShareUrlGenerator->generate('platform_message_launch_deep_linking', $form),
                    'deepLinkingLaunchRequest' => $deepLinkingLaunchRequest,
                    'editorClaims' => $this->parameterBag->get('editor_claims')
                ]
            )
        );
    }
}
