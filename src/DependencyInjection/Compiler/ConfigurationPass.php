<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Configuration;
use OAT\Bundle\Lti1p3Bundle\DependencyInjection\Lti1p3Extension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $configuration = (new Processor())->processConfiguration(
            new Configuration(),
            $container->getExtensionConfig(Lti1p3Extension::ALIAS)
        );

        $container->setParameter('lti1p3_resolved_configuration', $configuration);
    }
}
