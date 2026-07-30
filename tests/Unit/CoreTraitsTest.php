<?php

/**
 * SPDX-FileCopyrightText: 2021-2026 Open Assessment Technologies S.A.
 * Copyright (C) 2026 (original work) Open Assessment Technologies S.A.
 *
 * SPDX-License-Identifier: AGPL-3.0-only OR LicenseRef-TAO-Commercial-License
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
