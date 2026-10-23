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
 */

declare(strict_types=1);

namespace App\Runtime;

use Runtime\FrankenPhpSymfony\Runtime;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * FrankenPHP runtime for this application.
 *
 * In worker mode, uses the rebooting FrankenPhpWorkerRunner so the Symfony
 * kernel is rebooted (services reset via kernel.reset) between requests.
 * Outside worker mode (classic FrankenPHP mode, CLI, tests), behavior is
 * identical to runtime/frankenphp-symfony (shared-nothing per request).
 */
class FrankenPhpRuntime extends Runtime
{
    public function getRunner(?object $application): RunnerInterface
    {
        if ($application instanceof HttpKernelInterface && ($_SERVER['FRANKENPHP_WORKER'] ?? false)) {
            return new FrankenPhpWorkerRunner($application, (int) $this->options['frankenphp_loop_max']);
        }

        return parent::getRunner($application);
    }
}
