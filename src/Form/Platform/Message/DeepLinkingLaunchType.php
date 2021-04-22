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

namespace App\Form\Platform\Message;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Resource\File\FileInterface;
use OAT\Library\Lti1p3Core\Resource\HtmlFragment\HtmlFragmentInterface;
use OAT\Library\Lti1p3Core\Resource\Image\ImageInterface;
use OAT\Library\Lti1p3Core\Resource\Link\LinkInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLinkInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DeepLinkingLaunchType extends AbstractType
{
    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(RegistrationRepositoryInterface $repository, ParameterBagInterface $parameterBag)
    {
        $this->repository = $repository;
        $this->parameterBag = $parameterBag;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userChoices = array_keys($this->parameterBag->get('users'));
        
        $acceptTypesChoices = [
            LinkInterface::TYPE,
            LtiResourceLinkInterface::TYPE,
            FileInterface::TYPE,
            ImageInterface::TYPE,
            HtmlFragmentInterface::TYPE,
        ];
        
        $acceptPresentationChoices = [
            'iframe',
            'window',
            'embed',
        ];

        $builder
            ->add(
                'registration',
                ChoiceType::class,
                [
                    'choices' => $this->repository->findAll(),
                    'help' => 'Will use the selected registration tool as target'
                ]
            )
            ->add(
                'user_type',
                ChoiceType::class,
                [
                    'label' => '<label>User</label>',
                    'label_html' => true,
                    'label_attr' => [
                        'class' => 'radio-inline'
                    ],
                    'choices' => [
                        'from list' => 'list',
                        'custom' => 'custom'
                    ],
                    'expanded' => true,
                    'multiple' => false,
                    'required' => false,
                    'placeholder' => 'anonymous',
                    'empty_data' => 'anonymous',
                    'help' => 'User for the launch'
                ]
            )
            ->add(
                'user_list',
                ChoiceType::class,
                [
                    'choices' => array_combine($userChoices, $userChoices),
                    'required' => true,
                    'help' => 'List of configured users available for launch'
                ]
            )
            ->add(
                'custom_user_id',
                TextType::class,
                [
                    'label' => 'Custom user identifier',
                    'required' => false,
                    'help' => 'Custom user identifier (sub claim)'
                ]
            )
            ->add(
                'custom_user_name',
                TextType::class,
                [
                    'required' => false,
                    'help' => 'Custom user name (name claim)'

                ]
            )
            ->add(
                'custom_user_email',
                TextType::class,
                [
                    'required' => false,
                    'help' => 'Custom user email (email claim)'

                ]
            )
            ->add(
                'custom_user_locale',
                TextType::class,
                [
                    'required' => false,
                    'help' => 'Custom user locale (locale claim)'

                ]
            )
            ->add(
                'accept_types',
                ChoiceType::class,
                [
                    'label' => 'Accepted types',
                    'choices' => array_combine($acceptTypesChoices, $acceptTypesChoices),
                    'required' => true,
                    'multiple' => true,
                    'help' => 'Accepted content item types'
                ]
            )
            ->add(
                'accept_presentation_document_targets',
                ChoiceType::class,
                [
                    'label' => 'Accepted targets',
                    'choices' => array_combine($acceptPresentationChoices, $acceptPresentationChoices),
                    'required' => true,
                    'multiple' => true,
                    'help' => 'Accepted document targets'
                ]
            )
            ->add(
                'deep_linking_url',
                TextType::class,
                [
                    'label' => 'Launch url',
                    'required' => false,
                    'help_html' => true,
                    'help' => 'If provided, will be the url where to send the <code>LtiDeepLinkingRequest</code> message. If not, will use the selected registration tool default deep linking url'

                ]
            )
            ->add(
                'accept_media_types',
                TextType::class,
                [
                    'label' => 'Accepted media types',
                    'required' => false,
                    'help' => 'Accepted media types, comma separated'

                ]
            )
            ->add(
                'accept_multiple',
                ChoiceType::class,
                [
                    'label' => 'Accept multiple',
                    'choices' => [
                        'yes' => true,
                        'no' => false,
                    ],
                    'empty_data' => true,
                    'required' => true,
                    'help' => 'If should accept multiple content items'
                ]
            )
            ->add(
                'auto_create',
                ChoiceType::class,
                [
                    'label' => 'Auto create',
                    'choices' => [
                        'no' => false,
                        'yes' => true,
                    ],
                    'empty_data' => false,
                    'required' => true,
                    'help' => 'If should auto create the content item on tool side'
                ]
            )
            ->add(
                'claims',
                TextareaType::class,
                [
                    'required' => false,
                    'attr' => ['rows' => 15],
                    'help' => 'JSON formatted claims to add to the launch'
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => '<i class="fas fa-cogs"></i>&nbsp;Generate',
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
