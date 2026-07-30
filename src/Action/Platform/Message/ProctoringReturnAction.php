<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
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

    public function __invoke(Request $request, string $identifier): Response
    {
        $this->flashBag->add(
            'success',
            sprintf(
                'Platform LtiStartAssessment launch success%s',
                !empty($identifier) ? sprintf(' (for %s)', $identifier) : ''
            )
        );

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
