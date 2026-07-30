<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Form\Platform\Nrps;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotEqualTo;

class MembershipType extends AbstractType
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
        $builder
            ->add(
                'membership_id',
                TextType::class,
                [
                    'label' => 'Membership identifier',
                    'required' => true,
                    'help' => 'Membership identifier',
                    'disabled' => $options['edit'] ?? false,
                    'constraints' => [new NotEqualTo('default')],
                ]
            )
            ->add(
                'context_id',
                TextType::class,
                [
                    'label' => 'Context identifier',
                    'required' => true,
                    'help' => 'Membership context identifier'
                ]
            )
            ->add(
                'context_label',
                TextType::class,
                [
                    'label' => 'Context label',
                    'required' => false,
                    'help' => 'Membership context label'
                ]
            )
            ->add(
                'context_title',
                TextType::class,
                [
                    'label' => 'Context title',
                    'required' => false,
                    'help' => 'Membership context title'
                ]
            )
            ->add(
                'members',
                TextareaType::class,
                [
                    'required' => false,
                    'attr' => ['rows' => 18],
                    'help' => 'JSON formatted members'
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
