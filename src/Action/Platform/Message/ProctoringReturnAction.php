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

namespace App\Action\Platform\Message;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

class ProctoringReturnAction
{
    /** @var FlashBagInterface */
    private $flashBag;

    /** @var Environment */
    private $twig;

    /** @var Security */
    private $security;

    public function __construct(
        FlashBagInterface $flashBag,
        Environment $twig,
        Security $security
    )
    {
        $this->flashBag = $flashBag;
        $this->twig = $twig;
        $this->security = $security;
    }

    public function __invoke(Request $request): Response
    {
        $this->flashBag->add('success', 'Platform LtiStartAssessment launch success');

        return new Response(
            $this->twig->render(
                'platform/message/proctoringReturn.html.twig',
                [
                    'token' => $this->security->getToken()
                ]
            )
        );
    }
}
