<?php

/**
 * SPDX-FileCopyrightText: 2020-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
 */

declare(strict_types=1);

namespace App\Form\Generator;

use App\Generator\UrlGenerator;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use Symfony\Component\Form\FormInterface;

class FormShareUrlGenerator
{
    /** @var UrlGenerator */
    private $generator;

    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function generate(string $url, FormInterface $form): string
   {
       $queryParams = array_map(
           static function ($value) {
               return $value instanceof RegistrationInterface
                   ? $value->getIdentifier()
                   : $value;
           },
           $form->getData()
       );

       return $this->generator->generate($url, $queryParams);
   }
}
