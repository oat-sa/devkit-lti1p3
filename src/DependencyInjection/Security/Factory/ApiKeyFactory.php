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

namespace App\DependencyInjection\Security\Factory;

use App\Security\Api\Authenticator\ApiKeyAuthenticator;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ApiKeyFactory implements AuthenticatorFactoryInterface
{
    // Same band as http_basic — runs before lti1p3 service authenticators.
    public const PRIORITY = -40;

    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    public function getKey(): string
    {
        return 'api_key';
    }

    public function addConfiguration(NodeDefinition $builder): void
    {
        // Enabled with `api_key: true` — no firewall-level options.
    }

    public function createAuthenticator(
        ContainerBuilder $container,
        string $firewallName,
        array $config,
        string $userProviderId
    ): string|array {
        $authenticatorId = 'security.authenticator.api_key.' . $firewallName;

        $container
            ->setDefinition($authenticatorId, new ChildDefinition(ApiKeyAuthenticator::class))
            ->setArgument('$parameterBag', new Reference('parameter_bag'))
            ->setArgument('$logger', new Reference('logger'));

        return $authenticatorId;
    }
}
