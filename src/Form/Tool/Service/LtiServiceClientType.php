<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Form\Tool\Service;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

class LtiServiceClientType extends AbstractType
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(RegistrationRepositoryInterface $repository, ParameterBagInterface $parameterBag)
    {
        $this->repository = $repository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $httpMethodChoices = [
            'GET' => 'GET',
            'POST' => 'POST',
            'PUT' => 'PUT',
            'PATCH' => 'PATCH',
            'DELETE' => 'DELETE',
        ];

        $builder
            ->add(
                'registration',
                ChoiceType::class,
                [
                    'choices' => $this->repository->findAll(),
                    'help' => "Will use the selected registration's platform as target"
                ]
            )
            ->add(
                'service_url',
                UrlType::class,
                [
                    'label' => 'Service Url',
                    'help' => "Url of the selected registration's platform service endpoint to call"
                ]
            )
            ->add(
                'method',
                ChoiceType::class,
                [
                    'choices' => array_combine($httpMethodChoices, $httpMethodChoices),
                    'label' => 'Method',
                    'help' => "HTTP Method to perform the call with"
                ]
            )
            ->add(
                'body',
                TextareaType::class,
                [
                    'required' => false,
                    'attr' => ['rows' => 12],
                    'help' => "Request body to perform the call with"
                ]
            )
            ->add(
                'scope',
                TextType::class,
                [
                    'label' => 'Scopes',
                    'help' => "Scopes to provide to the selected registration's platform access token endpoint"
                ]
            )
            ->add(
                'media',
                TextType::class,
                [
                    'required' => false,
                    'label' => 'Media type',
                    'help' => 'Request accept or content type header to perform the call with'
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => '<i class="fas fa-sign-in-alt"></i>&nbsp;Call LTI service',
                    'label_html' => true,
                    'attr' => ['class' => 'btn-primary']
                ]
            )
        ;

        $builder
            ->get('registration')
            ->addModelTransformer(new CallbackTransformer(
                function (?string $registrationIdentifier) {
                    return $registrationIdentifier
                        ? $this->repository->find($registrationIdentifier)
                        : null;
                },
                function (?RegistrationInterface $registration) {
                    return $registration;
                }
            ))
        ;
    }
}
