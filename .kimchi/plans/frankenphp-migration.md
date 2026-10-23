# Migrate devkit-lti1p3 from Nginx + PHP-FPM / NGINX Unit to FrankenPHP (worker mode)

## Goal

Serve the devkit-lti1p3 Symfony 5.4 application in Docker without Nginx, PHP-FPM, or NGINX Unit:
one application container running FrankenPHP serving HTTP directly on port 8080 (static files +
PHP + persistent Symfony kernel in worker mode), plus Redis. Update local Compose, Cloud Build
Dockerfile, docs, and validate with a repeatable build→run→test loop.

## Repository assessment (baseline @ master 3f5bc18)

- Symfony **5.4.24** (framework-bundle), http-kernel **5.4.48**, PHP declared >=8.2; lock file
  effectively pins PHP **8.2–8.4** via lcobucci/jwt 5.5.0 + lcobucci/clock 3.3.1
  (league/oauth2-server 8.5.5, used by oat-sa/bundle-lti1p3 7.1.1). PHP 8.5 fails `composer install`.
- No `symfony/runtime` installed; classic `public/index.php` front controller with
  TRUSTED_PROXIES/TRUSTED_HOSTS handling; `config/preload.php` opcache preload.
- Kernel: `MicroKernelTrait` with legacy `RouteCollectionBuilder` (Symfony ≤5 API), custom compiler
  passes (ConfigurationPass, RedisPass), ApiKeyFactory security listener.
- Firewalls: `^/api/` api-key (stateless), `^/tool/launch` + LTI message/service firewalls (all
  stateless), `main` anonymous lazy. No form login; session handler `null` (native files), barely used.
- State: Redis via `cache.adapter.redis` (default_redis_provider from REDIS_CACHE_DSN) used by
  LTI bundle (nonces, registrations cache) and app repositories (AGS/NRPS/BO). No Doctrine.
- PSR-7 stack already present: nyholm/psr7 1.8.2 + symfony/psr-http-message-bridge 2.3.1 +
  Guzzle 7.9.2 (LTI service clients perform server-to-server HTTP, e.g. JWKS fetch).
- Tests: `tests/Unit/CoreTraitsTest.php` only (phpunit 8.5 via symfony/phpunit-bridge).
- Local dev request flow: Traefik :80 → nginx (alias devkit-lti1p3.localhost) → fastcgi →
  php-fpm :9000 → Symfony; Redis :6379; redis-commander :8081.
- Production flow (kube): `docker/kube/Dockerfile` = composer stage + NGINX Unit PHP image
  (both private base images: europe-west1-docker.pkg.dev/tao-artefacts/...), `unit.json` routes
  `/health-check` → direct `200` **without reaching PHP** (not a real app health signal), everything
  else → Unit PHP app (max 16 processes). Cloud Build builds that Dockerfile, exposes 8080.

## Decision

Comparison (criteria weighted for THIS repo: locked Symfony 5.4 + PHP 8.4, stateless LTI app,
single-container serving, build simplicity, governance/maintenance):

| Criterion | RoadRunner + baldinof 3.4.0 | FrankenPHP worker | FrankenPHP classic | Swoole/OpenSwoole |
|---|---|---|---|---|
| Symfony 5.4 / PHP 8.4 compat | Yes (3.x via symfony/runtime) | Yes (runtime/frankenphp-symfony ^5.4) | Yes (no PHP deps) | Risky (blocking Guzzle clients, coroutine model) |
| Kernel reuse (persistent worker) | Yes | Yes | **No** (removes FPM only) | Yes |
| Static files in same container | Via RR `static` plugin | Native (Caddy `file_server` inside `php_server`) | Native | Manual |
| Image/build complexity | High (Go binary fetch via `rr get-binary`/GitHub releases, goridge relay, spiral/* deps) | Low (official maintained image, single binary) | Low | Medium-high (PECL + async discipline) |
| Maintenance/governance | Bundle active (3.4.0, 2026-05) but single-maintainer; CI shows PHP 8.4 static-analysis failures | Project under github.com/php; daily Docker rebuilds; 1.13.2 = security release for PHP2Shell CVE-2026-109618/109619 (same CVE the repo just patched with unit-php 8.4.27) | same | Merged back to Swoole; steep async requirements |
| Extra ecosystem (jobs/gRPC/kv) | Rich — not needed by this repo | Not needed | Not needed | Coroutines — not needed |
| Observability | Prometheus plugin | Caddy metrics endpoint | Caddy metrics | Manual |

**Selected: FrankenPHP 1.13.x worker mode** (image `dunglas/frankenphp:<pinned>-php8.4-bookworm`),
integration via `symfony/runtime` 5.4.x + `runtime/frankenphp-symfony` 1.0.x.
Classic mode (`php_server` without `worker` block) documented as fallback if worker-mode issues appear.

Rejected: RoadRunner (no functional advantage for this repo, heavier supply chain), Swoole
(async model incompatible with blocking Guzzle/LTI libs, steepest risk), keeping Nginx Unit
(current state; fails task requirement), Nginx+PHP-FPM (baseline only, rejected as target).

References: roadrunner.dev docs (integrations/symfony lists baldinof/FluffyDiscord/php-runtime
bundles), docs.roadrunner.dev Symfony page; frankenphp.dev docs (worker.md, config.md, docker.md,
symfony.md); Packagist baldinof/roadrunner-bundle 3.4.0 (requires symfony ^5.4–^8, RR 2023–2025,
php >=8.1); Packagist runtime/frankenphp-symfony 1.0.0 (requires symfony/runtime ^5.4 ||
^6 || ^7, php >=8.1); github.com/php/frankenphp releases v1.13.3 (2026-10-23) / v1.13.2 security
release; deployhq.com comparison article (2026-02).

## Constraints

- Do not upgrade Symfony (stays 5.4.x) or the OAT LTI libraries; framework entry-front-controller
  change only. New composer deps limited to: `symfony/runtime` (^5.4.x branch), `runtime/frankenphp-symfony` (^1.0).
- PHP base must be 8.4.x (never 8.5) — lock file constraint.
- Preserve routes, CLI commands, Redis data (same DSN/namespace semantics), env vars, LTI behavior.
- Production image must contain code + vendor, run without bind mounts, log to stdout, prod defaults
  (APP_ENV=prod, APP_DEBUG=0), graceful shutdown, configurable concurrency/recycling.
- Do not deploy/publish/merge. Work on a branch. Private Artifact Registry base images are NOT
  pullable from this environment → replace with public bases; report that cloud build itself is unvalidated.
- Static files restricted to `public/`.

## Chunks

### Chunk 1 — Composer + runtime front controller

Files changed: `composer.json`, `composer.lock`, `public/index.php`, (verify no changes needed in `config/`).

- `composer require symfony/runtime:^5.4 runtime/frankenphp-symfony:^1.0 --no-update`, then
  `composer update symfony/runtime runtime/frankenphp-symfony --with-dependencies` under PHP 8.4.
- Rewrite `public/index.php` to the Symfony Runtime front controller returning the Kernel closure;
  port the existing TRUSTED_PROXIES / TRUSTED_HOSTS / APP_DEBUG logic into the closure
  (setTrustedProxies is static app config → runs once per worker boot, correct under worker mode).
- Verify in vendor how runtime/frankenphp-symfony + symfony/runtime 5.4 handle kernel reboot /
  services reset between requests; if reboot is not automatic on 5.4, add the documented
  `SymfonyComponentHttpKernelRebootableInterface` handling or configure
  `frankenphp_loop_max` (default 500) as bounded mitigation and record the finding.

Depends on: baseline composer install working.
Accept when: `vendor/bin/simple-phpunit` still passes; `APP_RUNTIME=Runtime\FrankenPhpSymfony\Runtime
php -r` smoke boots kernel; bin/console boot works (Runtime must not break CLI: `bin/console` does
not use index.php, safe by construction).
Test Coverage: existing unit tests + new validation script smoke (chunk 4).
Open questions: exact reboot semantics of symfony/runtime 5.4 in worker mode (resolved by vendor
inspection + empiric isolation test in chunk 4).

### Chunk 2 — FrankenPHP application image

Files changed: `docker/frankenphp/Dockerfile` (new), `docker/frankenphp/Caddyfile` (new),
`php.ini` (adapt; consumed by all images), `.dockerignore` (verify coverage).

- Multi-stage: (1) composer stage `php:8.4-cli-bookworm` + composer from `composer/composer:2-bin`,
  `composer install --no-dev --optimize-autoloader`, classmap-authoritative, cache warmup
  (APP_ENV=prod bin/console cache:warmup).
- (2) runtime `dunglas/frankenphp:1.13.2-php8.4-bookworm` (pinned; 1.13.2 = PHP2Shell fix);
  install ext intl, zip, opcache, sockets, pcntl, redis (PECL pinned) via docker-php-ext-*;
  copy php.ini → conf.d adapted (opcache.preload path /app/config/preload.php,
  opcache.preload_user=www-data — verify preload works on ZTS; fallback: disable preload, keep rest);
  copy app from composer stage; `chown` var/ for www-data; ENTRYPOINT default frankenphp with
  Caddyfile; EXPOSE 8080.
- Caddyfile: global { admin 127.0.0.1:2019 (for worker-restart checks), servers trusted_proxies
  static private_ranges, frankenphp { php_ini ..., max_requests default 0 } };
  site: `http://:8080`, root * /app/public, encode zstd gzip, `php_server { worker { file
  /app/public/index.php, num {$WORKER_NUM:2}, env APP_ENV {$APP_ENV}, watch (dev only via separate
  Caddyfile.dev) } }`, access log to stdout.
- Env-configurable: WORKER_NUM, runtime loop via `frankenphp_loop_max` (default 500),
  TRUSTED_PROXIES passthrough (Symfony-side header trust already in index.php).

Depends on: Chunk 1 (runtime index.php).
Accept when: image builds; container starts; serves dashboard + CSS from public/; /config, ../src
not reachable (404); prod cache prebuilt; graceful stop (SIGTERM → clean exit, in-flight request
finishes or is bounded).
Test Coverage: validation script checks (chunk 4).
Open questions: none blocking; preload-on-ZTS verified at build.

### Chunk 3 — Compose + Cloud Build + legacy cleanup

Files changed: `docker-compose.yml`, `docker/kube/Dockerfile`, `cloudbuild.yaml` (only if needed),
`unit.json` (delete), `docker/nginx/` (delete), `docker/phpfpm/` (delete), `README.md`,
`doc/installation.md` (+ new `doc/frankenphp.md`).

- Compose: service `devkit_lti1p3_app` (build ./docker/frankenphp, dev-friendly: bind mount
  `.:/app` + dev Caddyfile with watch + APP_ENV=dev), traefik labels Host(devkit-lti1p3.localhost)
  → app:8080; keep redis + redis-commander; expose 8080 for direct access; drop nginx + phpfpm services.
- kube Dockerfile: same FrankenPHP content as chunk 2 but fully prod (no mount), using **public**
  bases (php:8.4-cli-bookworm + dunglas/frankenphp pinned); cloudbuild.yaml keeps
  `-f ./docker/kube/Dockerfile .` path — likely no edit needed.
- Delete unit.json, docker/nginx, docker/phpfpm; document rollback via git checkout/master paths.

Depends on: Chunk 2.
Accept when: `docker compose up -d` yields a working app at http://devkit-lti1p3.localhost AND
http://localhost:8080; kube Dockerfile builds locally.
Test Coverage: validation script runs against both compose project and kube-built image.
Open questions: —

### Chunk 4 — Repeatable validation script + worker-mode hardening checks

Files changed: `bin/validate-frankenphp.sh` (new), possibly `.github/workflows` (none exist for
CI tests — only sonar; leave untouched).

Script behavior: fixed project name `devkit-validate`, `docker compose -p devkit-validate ... up`,
bounded readiness wait (60s), assertions via curl:
- prod boot: `bin/console about` + `cache:clear` inside container; container logs show no errors.
- Dashboard `/` = 200 with expected marker; one CSS asset under /css? (templates use CDN assets —
  verify an actually-local asset path; fallback assertion: /bundles/... none → assert on
  /favicon or check built-in `php-info` page 200).
- Real health: `/health-check` (HealthCheckBundle) returns app-level 200 (proves Symfony+checkers,
  incl. Redis when configured) — NOT a server-level 200.
- Redis: `bin/console cache:pool:clear cache.app` succeeds (proves ext-redis + DSN).
- API auth: `GET /api/platform/nrps/memberships` **401** without key, **200** with
  `Authorization: Bearer <APP_API_KEY>` (exact header name verified in ApiKeyListener).
- Sessions/cookies/redirects: LTI platform message launch pages render forms (GET 200);
  POST an LTI deep-linking/proctoring flow where scriptable; verify Set-Cookie + 302 chains.
- Trusted proxy: send `X-Forwarded-Proto: https` + `X-Forwarded-Host` with TRUSTED_PROXIES set →
  generated absolute URLs in an LTI launch form (action attribute) use https/host.
- LTI flow end-to-end (local): use the devkit against itself — CLI or curl: build an OIDC
  initiation POST → platform `/lti1p3/oidc/authentication` → expect 302 to tool `/tool/launch`
  → POST signed JWT (test keys in config/keys) → expect success page; assert JWKS endpoint JSON
  200; repeat POST with same nonce → expect replay rejection (bundle nonce cache via Redis) —
  assert the **bundle's** error semantics, do not fabricate.
- Worker isolation (single worker, `WORKER_NUM=1`): (a) confirm kernel reuse (response header or
  log signal — empiric: memory stable + boot-time log appears once); (b) alternate
  authenticated/anon API calls ×20 — assert no cross-contamination (each unauth call still 401);
  (c) trigger application exception (invalid AGS payload → 400/500 via ErrorHandlerSubscriber) →
  subsequent requests still healthy; (d) sustained loop ~2000 requests, record `docker stats`
  memory start/end (report slope; no leak claim beyond measured window); (e) worker restart via
  Caddy admin API POST /frankenphp/workers/restart → service recovers; (f) docker stop during
  in-flight slow request → termination bounded ≤ stop timeout.
- Exit codes; logs saved under `.kimchi/docs/validation-<ts>/`; `docker compose -p ... down -v`
  cleanup of own project only.

Depends on: Chunks 1–3.
Accept when: full clean run green; failures diagnosed & fixed in loop.
Test Coverage: this script IS the coverage; plus existing phpunit.
Open questions: exact ApiKey header semantics; nonce storage backend (cache.app/redis) — resolved
during implementation from vendor bundle code.

### Chunk 5 — Benchmarks + docs + report

Files changed: `doc/frankenphp.md` (new), `doc/installation.md`, `README.md`,
`.kimchi/docs/report.md` (final report), maybe `bin/bench.sh`.

- Baseline bench: legacy stack (nginx+fpm compose from git master, vendor bind-mounted, same
  Redis) vs FrankenPHP worker (num=2) vs FrankenPHP classic; same route (dashboard GET /, real
  Twig render + Redis-backed cache reads), same concurrency (e.g. hey -c 20 -d 30s), container
  CPU/mem limits equal (cpus=1.0, memory=768m), cold vs warm separated.
- Docs: new installation/ops commands, env matrix, permissions, rollback.
- Report: assessment, decision table, PASS/FAIL/BLOCKED matrix, logs, benchmark artifacts, risks.

Depends on: Chunk 4 green.
Accept when: report written, all artifacts saved under .kimchi/docs/, memory saved.
Test Coverage: n/a (analysis chunk).
Open questions: whether legacy stack pulls cleanly (nginx:stable, php:8.4-fpm build) — if blocked,
mark benchmark baseline BLOCKED and still bench worker vs classic modes.

## Verification Strategy

Layered: (1) static — composer validate, diff review; (2) unit — existing phpunit unchanged;
(3) container — image build + boot checks; (4) HTTP integration — validation script assertions
above incl. auth negatives and LTI protocol flow against local keys/jwks; (5) worker-mode soak —
isolation, exception-poisoning, restart, memory-window; (6) comparative benchmark. Every failure
→ logs → targeted fix → rerun affected, then one full clean final run. Nothing is marked PASS
without the recorded command + output artifact.

## Decision Log

- FrankenPHP worker over RoadRunner: equal compat, simpler supply chain, stronger governance;
  RR's queue/gRPC/otel strengths unused by this repo. Reversible decision (index.php runtime
  front controller is server-agnostic; RR could be adopted later cheaply).
- symfony/runtime entrypoint over hand-rolled `frankenphp_handle_request` loop: canonical,
  kernel reboot + Dotenv handled by the runtime; custom loop kept as documented alternative only
  if runtime proves inadequate (then explicit catch/terminate logic required).
- Public base images in docker/kube/Dockerfile: private AR images unpullable here; cloudbuild
  path preserved so CI diff is minimal. Limitation reported, not hidden.
- Keep Traefik in compose (optional dev proxy, task allows); app ALSO reachable directly :8080.
- php.ini: keep repo's opcache/max_execution_time tuning; preload_user adapted (www-data);
  drop Unit-specific path only if preload works on ZTS build.
- unit.json deleted: its `/health-check` short-circuit never proved app health; HealthCheckBundle
  routes now answer it for real.

## Risks

- Symfony 5.4 + runtime 5.4 worker reboot semantics unverified until vendor inspection (mitigation:
  chunk-1 inspection + chunk-4 isolation tests; worst case bump is a scoped composer constraint
  question, not a framework upgrade).
- opcache.preload on frankenphp ZTS may warn/fail → fallback: no preload, still opcache+JIT off
  matches current prod tuning (opcache.jit=0 already).
- Session storage on native files inside single container is fine (no multi-replica here);
  documented.
- PHP 8.4 deprecations noise from Symfony 5.4 code — matches existing Unit PHP 8.4.27 runtime;
  no behavior change.
- Cloud Build cannot be executed here → BLOCKED item, cloudbuild.yaml validated only statically.
- LTI replay test asserts bundle behavior as implemented; if nonce storage isn't enabled, test
  records the finding honestly instead of asserting nonexistent protection.
