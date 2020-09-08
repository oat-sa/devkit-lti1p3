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

namespace App\Form\Tool\Service;

use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
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
        $builder
            ->add(
                'registration',
                ChoiceType::class,
                [
                    'choices' => $this->repository->findAll(),
                    'help' => "Will use the selected registration's platform as target"
                ]
            )
            ->add('service_url', UrlType::class, [
                'label' => 'Service Url',
                'help' => "Url of the selected registration's platform service endpoint to call"
            ])
            ->add('method', ChoiceType::class, [
                'choices' => [
                    'GET' => 'GET',
                    'POST' => 'POST',
                    'PUT' => 'PUT',
                    'PATCH' => 'PATCH',
                    'DELETE' => 'DELETE',
                ],
                'label' => 'HTTP Method',
                'help' => "HTTP method to perform the call with"
            ])
            ->add('body', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 5],
                'help' => "Request body to perform the call with"
            ])
            ->add('scope', TextType::class, [
                'label' => 'Scopes',
                'help' => "Scopes to provide to the selected registration's platform access token url"
            ])
            ->add('Submit', SubmitType::class, ['label' => 'Call service'])
        ;
    }
}
