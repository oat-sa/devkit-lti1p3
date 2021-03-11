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

namespace App\Action\Platform\Nrps;

use App\Form\Platform\Nrps\MembershipType;
use App\Nrps\MembershipRepository;
use OAT\Library\Lti1p3Nrps\Factory\Member\MemberFactoryInterface;
use OAT\Library\Lti1p3Nrps\Model\Context\Context;
use OAT\Library\Lti1p3Nrps\Model\Member\MemberCollection;
use OAT\Library\Lti1p3Nrps\Model\Membership\Membership;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class CreateMembershipAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var MembershipRepository */
    private $repository;

    /** @var Environment */
    private $twig;

    /** @var FormFactoryInterface */
    private $factory;

    /** @var RouterInterface */
    private $router;

    /** @var MemberFactoryInterface */
    private $memberFactory;

    public function __construct(
        FlashBagInterface $flashBag,
        MembershipRepository $repository,
        Environment $twig,
        FormFactoryInterface $factory,
        RouterInterface $router,
        MemberFactoryInterface $memberFactory
    ) {
        $this->flashBag = $flashBag;
        $this->repository = $repository;
        $this->twig = $twig;
        $this->factory = $factory;
        $this->router = $router;
        $this->memberFactory = $memberFactory;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->factory->create(MembershipType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();

            $memberCollection = new MemberCollection();
            $members = [];

            if ($formData['members']) {
                $members = json_decode($formData['members'], true);

                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new BadRequestHttpException(sprintf('json_decode error: %s', json_last_error_msg()));
                }
            }

            foreach ($members as $member) {
                $memberCollection->add($this->memberFactory->create($member));
            }

            $membership = new Membership(
                 $formData['membership_id'],
                new Context(
                    $formData['context_id'],
                    $formData['context_label'] ?? null,
                    $formData['context_title'] ?? null
                ),
                $memberCollection
            );

            $this->repository->save($membership);

            $this->flashBag->add('success', sprintf('Membership %s creation success', $formData['membership_id']));

            return new RedirectResponse(
                $this->router->generate('platform_nrps_view_membership', ['membershipIdentifier' => $formData['membership_id']])
            );
        }

        return new Response(
            $this->twig->render(
                'platform/nrps/createMembership.html.twig',
                [
                    'form' => $form->createView(),
                ]
            )
        );
    }
}
