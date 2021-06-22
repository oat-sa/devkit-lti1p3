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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\Tests\Unit;

use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CoreTraitsTest extends KernelTestCase
{
    use DomainTestingTrait;

    /**
     * Test to ensure core lib testing traits can be used for SF application tests
     */
    public function testCoreTraitsUsage(): void
    {
        $registration = $this->createTestRegistration();

        $token = $this->buildJwt(
            [],
            [
                'some' => 'value'
            ],
            $registration->getPlatformKeyChain()->getPrivateKey()
        );

        $parsedToken = $this->parseJwt($token->toString());

        $this->assertTrue($this->verifyJwt($parsedToken, $registration->getPlatformKeyChain()->getPublicKey()));
        $this->assertEquals('value', $parsedToken->getClaims()->get('some'));
    }
}
