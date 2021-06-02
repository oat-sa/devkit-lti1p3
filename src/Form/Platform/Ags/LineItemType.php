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

namespace App\Form\Platform\Ags;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
                'submit',
                SubmitType::class, [
                    'label' => '<i class="fas fa-save"></i>&nbsp;Save',
                    'label_html' => true,
                    'attr' => ['class' => 'btn-primary']
                ]
            );
    }
}
