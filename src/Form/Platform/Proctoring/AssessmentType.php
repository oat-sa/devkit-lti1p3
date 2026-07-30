<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Form\Platform\Proctoring;

use OAT\Library\Lti1p3Proctoring\Model\AcsControlResultInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssessmentType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'edit' => false,
            ]
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $statuses = array_combine(
            AcsControlResultInterface::SUPPORTED_STATUSES,
            AcsControlResultInterface::SUPPORTED_STATUSES
        );

        $builder
            ->add(
                'assessment_id',
                TextType::class,
                [
                    'label' => 'Identifier',
                    'required' => true,
                    'help' => 'Assessment identifier',
                    'disabled' => $options['edit'] ?? false
                ]
            )
            ->add(
                'assessment_status',
                ChoiceType::class,
                [
                    'label' => 'Status',
                    'required' => true,
                    'help' => 'Assessment status',
                    'choices' => $statuses
                ]
            )
            ->add(
                'submit',
                SubmitType::class, [
                    'label' => '<i class="fas fa-save"></i>&nbsp;Save',
                    'label_html' => true,
                    'attr' => ['class' => 'btn-primary']
                ]
            );
    }
}
