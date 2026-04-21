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
 * Copyright (c) 2026 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Request\ValueResolver;

use App\Request\Encoder\Base64UrlEncoder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class AgsLineItemIdentifierResolver implements ValueResolverInterface
{
    private const ARGUMENT_NAME = 'lineItemIdentifier';

    /**
     * @return iterable<string>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getName() !== self::ARGUMENT_NAME) {
            return [];
        }

        if (!$request->attributes->has(self::ARGUMENT_NAME)) {
            return [];
        }

        $raw = (string) $request->attributes->get(self::ARGUMENT_NAME);

        return [Base64UrlEncoder::decode($raw)];
    }
}
