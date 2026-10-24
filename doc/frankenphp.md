# FrankenPHP runtime

Since this change the development kit ships as a single-container PHP application
server: [FrankenPHP](https://frankenphp.dev) (Caddy + PHP in ZTS builds) running
the Symfony kernel in **worker mode**. There is no Nginx, no PHP-FPM and no
NGINX Unit anymore — the application server itself terminates HTTP on port 8080.

## Runtime model

| Aspect            | Choice                                                        |
|-------------------|---------------------------------------------------------------|
| Server            | FrankenPHP 1.13 (official `dunglas/frankenphp` images)        |
| Mode              | worker mode (`frankenphp_worker` Caddy directive)             |
| Symfony runtime   | `symfony/runtime` + `runtime/frankenphp-symfony`              |
| Application entry | `App\Runtime\FrankenPhpRuntime` (set in the worker Caddyfile) |
| Kernel lifecycle  | reboot + terminate between requests (see below)               |

### Why a custom worker runner

`App\Runtime\FrankenPhpWorkerRunner` (`src/Runtime/`) wraps the
`runtime/frankenphp-symfony` runner and additionally **reboots the kernel
between requests** (`Kernel::reboot()`), then calls `terminate()` and collects
cyclic garbage. This is what keeps LTI message state, security tokens and
sessions from leaking across requests in long-lived workers: Symfony 5.4's
HttpKernel has no reboot-aware worker loop of its own (that arrived with
Symfony 6), so the runner implements the loop explicitly.

`FRANKENPHP_LOOP_MAX` (worker env, default 500) caps the number of requests a
worker handles before recycling; Caddy restarts workers automatically, and
the Caddy admin API (loopback `:2019`) exposes
`POST /frankenphp/workers/restart` for manual recycling.

## Configuration knobs

| Variable              | Where                | Default | Meaning                              |
|-----------------------|----------------------|---------|--------------------------------------|
| `PORT`                | Caddyfile (prod)     | `8080`  | HTTP listen port                     |
| `WORKER_NUM`          | Caddyfile / compose  | `2` (prod), `1` (dev) | worker concurrency    |
| `FRANKENPHP_LOOP_MAX` | Caddyfile / compose  | `500`   | requests per worker before recycle   |
| `APP_RUNTIME`         | Caddyfile worker env | `App\Runtime\FrankenPhpRuntime` | runtime class |

`APP_RUNTIME` is set through the Caddyfile worker env (not `.env`) because the
runtime class is resolved before Dotenv loads in the worker bootstrap.

Production images bake `APP_ENV=prod` / `APP_DEBUG=0` as Docker `ENV` so CLI
commands (`bin/console`, validation helpers) run with the same defaults as the
workers regardless of `.env` fallbacks.

## PHP in ZTS workers — opcache preload is disabled

`php.ini` explicitly disables `opcache.preload`. Preloading the Symfony
container in FrankenPHP's ZTS worker threads corrupted the engine heap in
testing (`zend_mm_heap corrupted`, undefined `dirname()` during boot). The
kernel reboot-per-request model replaces the warmup role preloading would
play; do not re-enable it on this runtime.

`error_reporting` is trimmed of `E_DEPRECATED` in the shipped `php.ini` to keep
worker logs clean (Symfony 5.4 emits deprecation notices under PHP 8.4); the
phpunit deprecation policy is unchanged.

## Images

| Image                         | Purpose                                              |
|-------------------------------|------------------------------------------------------|
| `docker/frankenphp/Dockerfile`| local/dev stack (`docker-compose.yml`)               |
| `docker/kube/Dockerfile`      | production image for Cloud Build / k8s deployments   |

Both are multi-stage builds on public `dunglas/frankenphp:1.13.2-php8.4-bookworm`
bases. 1.13.2 is a security release: its Docker images ship PHP builds
(≥ 8.4.27) that fix PHP2Shell (CVE-2026-109618 / CVE-2026-109619, critical
unauthenticated RCE) plus four FrankenPHP vulnerabilities, among them a
worker-mode fix that clears `error_get_last()` between requests. Install
`intl`, `zip`, `opcache`, `sockets`, `pcntl`, `redis` via
`mlocati/php-extension-installer`, warm the Symfony caches at build time, and
run as `www-data`. Static assets are served from `public/` only; the Caddyfile
restricts file serving to that root.

## Local development

```console
$ docker compose up -d --build
```

The stack provides:

| Service                    | URL                                        |
|----------------------------|--------------------------------------------|
| app (FrankenPHP worker)    | http://localhost:8080                      |
| app via Traefik            | http://devkit-lti1p3.localhost             |
| Traefik dashboard          | http://localhost:8090                      |
| redis-commander            | http://localhost:8081                      |

Dev mode uses `Caddyfile.dev` (single worker, file watching) and `php-dev.ini`;
the app directory is bind-mounted and `/app/var` is a tmpfs.

## Validation harness

`bin/validate-frankenphp.sh` boots the production image (no bind mounts) in an
isolated compose project and exercises the acceptance surface:

```console
$ bin/validate-frankenphp.sh --quick   # S0-S7 functional checks
$ bin/validate-frankenphp.sh           # adds S8: single-worker isolation,
                                       # exception poisoning, 3000-request
                                       # memory soak, worker restart, graceful
                                       # stop, kube image build, phpunit
```

Artifacts (per-section logs, captured pages, app container logs) land in
`.kimchi/docs/validation-<timestamp>/`.
