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
            )
            ;
    }
}
