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
