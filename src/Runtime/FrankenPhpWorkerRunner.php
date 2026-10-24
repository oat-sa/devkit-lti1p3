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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\RebootableInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Runtime\RunnerInterface;

/**
 * FrankenPHP worker runner with a correct Symfony request lifecycle:
 *
 *  - creates an HttpFoundation request from the per-request superglobals
 *    injected by frankenphp_handle_request()
 *  - terminates the kernel after sending the response
 *  - reboots the kernel between requests so every kernel.reset tagged
 *    service (security token storage, session storage, usage tracking...)
 *    is cleared and no request state leaks into the next request
 *  - recycles the worker after $loopMax requests (bound for slow leaks)
 *  - catches fatal throwables so one bad request cannot kill the worker loop
 *    without leaving a trace
 */
final class FrankenPhpWorkerRunner implements RunnerInterface
{
    private HttpKernelInterface $kernel;

    private int $loopMax;

    public function __construct(HttpKernelInterface $kernel, int $loopMax)
    {
        $this->kernel = $kernel;
        $this->loopMax = $loopMax;
    }

    public function run(): int
    {
        // Prevent worker script termination when a client connection is interrupted.
        ignore_user_abort(true);

        $server = array_filter(
            $_SERVER,
            static fn (string $key) => !str_starts_with($key, 'HTTP_'),
            ARRAY_FILTER_USE_KEY
        );
        $server['APP_RUNTIME_MODE'] = 'web=1&worker=1';

        $request = $response = null;
        $handler = function () use ($server, &$request, &$response): void {
            $request = $response = null;

            // Merge the environment variables coming from Dotenv with the ones
            // tied to the current request (request-bound values win).
            $_SERVER += $server;

            $request = Request::createFromGlobals();
            $response = $this->kernel->handle($request);
            $response->send();
        };

        $loops = 0;
        do {
            try {
                $ret = \frankenphp_handle_request($handler);
            } catch (\Throwable $throwable) {
                // Kernel::handle() normally converts throwables to error responses;
                // if one still escapes (e.g. during send()), keep the worker alive.
                fwrite(STDERR, (string) $throwable);
                $ret = true;
            }

            if ($this->kernel instanceof TerminableInterface && null !== $request && null !== $response) {
                $this->kernel->terminate($request, $response);
            }

            if ($this->kernel instanceof RebootableInterface) {
                $this->kernel->reboot(null);
            }

            gc_collect_cycles();
        } while ($ret && (-1 === $this->loopMax || ++$loops < $this->loopMax));

        return 0;
    }
}
