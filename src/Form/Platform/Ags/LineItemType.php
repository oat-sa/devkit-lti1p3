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
        $builder
            ->add(
                'line_item_id',
                TextType::class,
                [
                    'label' => 'Identifier',
                    'required' => true,
                    'help' => 'Line item identifier',
                    'disabled' => $options['edit'] ?? false
                ]
            )
            ->add(
                'line_item_context_id',
                TextType::class,
                [
                    'label' => 'Context identifier',
                    'required' => true,
                    'help' => 'Line item context identifier'
                ]
            )
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
                'submit',
                SubmitType::class, [
                    'label' => '<i class="fas fa-save"></i>&nbsp;Save',
                    'label_html' => true,
                    'attr' => ['class' => 'btn-primary']
                ]
            );
    }
}
