<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Form\Platform\Ags;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LineItemType extends AbstractType
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
        if (!($options['edit'] ?? false)) {
            $builder
                ->add(
                    'line_item_id',
                    TextType::class,
                    [
                        'label' => 'Identifier',
                        'required' => true,
                        'help' => 'Line item identifier (url part)'
                    ]
                )
                ->add(
                    'line_item_context_id',
                    TextType::class,
                    [
                        'label' => 'Context identifier',
                        'required' => true,
                        'help' => 'Line item context identifier (url part)'
                    ]
                );
        } else {
            $builder
                ->add(
                    'line_item_id',
                    TextType::class,
                    [
                        'label' => 'Identifier',
                        'required' => true,
                        'help' => 'Line item identifier (full url)',
                        'disabled' => true
                    ]
                );
        }

        $builder
            ->add(
                'line_item_label',
                TextType::class,
                [
                    'label' => 'Label',
                    'required' => true,
                    'help' => 'Line item label'
                ]
            )
            ->add(
                'line_item_score_maximum',
                IntegerType::class,
                [
                    'label' => 'Score maximum',
                    'required' => true,
                    'help' => 'Line item score maximum'
                ]
            )
            ->add(
                'line_item_resource_id',
                TextType::class,
                [
                    'label' => 'Resource identifier',
                    'required' => false,
                    'help' => 'Line item resource identifier'
                ]
            )
            ->add(
                'line_item_resource_link_id',
                TextType::class,
                [
                    'label' => 'Resource link identifier',
                    'required' => false,
                    'help' => 'Line item resource link identifier'
                ]
            )
            ->add(
                'line_item_tag',
                TextType::class,
                [
                    'label' => 'Tag',
                    'required' => false,
                    'help' => 'Line item tag'
                ]
            )
            ->add(
                'line_item_start_date',
                TextType::class,
                [
                    'label' => 'Start date',
                    'required' => false,
                    'help' => 'Line item start date',
                    'attr' => [
                        'class' => 'form-control datetimepicker-input',
                        'data-target' => '#lineItemStartDateTimeDatetimepicker'
                    ]
                ]
            )
            ->add(
                'line_item_end_date',
                TextType::class,
                [
                    'label' => 'End date',
                    'required' => false,
                    'help' => 'Line item end date',
                    'attr' => [
                        'class' => 'form-control datetimepicker-input',
                        'data-target' => '#lineItemEndDateTimeDatetimepicker'
                    ]
                ]
            )
            ->add(
                'line_item_additional_properties',
                TextareaType::class,
                [
                    'required' => false,
                    'attr' => ['rows' => 10],
                    'help' => 'JSON formatted line item additional properties'
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
