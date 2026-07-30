<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

namespace App\Security\Api\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class ApiKeyToken extends AbstractToken
{
    public function __construct()
    {
        parent::__construct([]);

        $this->setAuthenticated(false);
    }

    public function getCredentials(): string
    {
        return '';
    }
}
