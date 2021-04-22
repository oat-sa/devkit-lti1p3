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
            )
            ;
    }
}
