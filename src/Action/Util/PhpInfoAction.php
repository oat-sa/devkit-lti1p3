<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Action\Util;

use Symfony\Component\HttpFoundation\Response;

class PhpInfoAction
{
    public function __invoke(): Response
    {
        ob_start();
        phpinfo();
        $info = ob_get_clean();

        return new Response($info);
    }
}
