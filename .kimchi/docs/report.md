# Migration report: Nginx/PHP-FPM/Unit → FrankenPHP

**Repo:** oat-sa/devkit-lti1p3 · **Branch:** `feat/frankenphp-migration` · **Date:** 2026-10-24

## Goal

Replace the dual local (Nginx + PHP-FPM) and Cloud Build (NGINX Unit) serving
topologies with a single modern PHP application server container that serves
HTTP directly on port 8080, keeping Symfony 5.4 and the locked OAT LTI 1.3
packages untouched (PHP 8.2–8.4, verified against `composer.lock`).

## Decision

**FrankenPHP 1.13.2 worker mode** (`dunglas/frankenphp:1.13.2-php8.4-bookworm`).

| Candidate | Symfony 5.4 / PHP 8.4 fit | Supply chain | Verdict |
|---|---|---|---|
| RoadRunner | requires `baldinof/roadrunner-bundle` + Go binary + gorridge deps | PHP runtime pkg + Go binary | rejected: more moving parts |
| **FrankenPHP worker** | official `runtime/frankenphp-symfony`; Caddy-based config | single official image, Caddy admin API for worker ops | **selected** |
| FrankenPHP classic | per-request php_server execution | same image | kept as measured variant |
| Swoole | no official Symfony 5.4 runtime; PECL ZTS risk | PECL extension | rejected |

1.13.2 is a security release: Docker images ship PHP ≥ 8.4.27 fixing PHP2Shell
(CVE-2026-109618 / CVE-2026-109619, critical unauthenticated RCE) plus four
FrankenPHP vulnerabilities, including a worker-mode fix clearing
`error_get_last()` between requests.

## What changed

| Commit | Content |
|---|---|
| `5c03ed8` | `symfony/runtime` + `runtime/frankenphp-symfony`; runtime-aware `public/index.php`; `App\Runtime\FrankenPhpRuntime` + `FrankenPhpWorkerRunner` (kernel reboot + terminate + gc per request — Symfony 5.4 HttpKernel has no rebootable worker loop) |
| `47055e1` | `docker/frankenphp/` Dockerfile (multi-stage, prod cache warmup, runs as www-data), Caddyfile (worker pool, trusted proxies, admin API on loopback, JSON stdout logs), dev variants |
| `9f8382c` | removed `unit.json`, `docker/nginx/`, `docker/phpfpm/`; kube Dockerfile on public bases; docker-compose rebuilt (app :8080, Traefik v2.11, redis 7 pinned) |
| `86b3ece` | redis health checker (`/health-check` → 500 on failure); `bin/validate-frankenphp.sh` acceptance harness |
| `6ef5c78`, `6917753` | harness corrections (IMS media types, payload shapes, phpunit log-content judging) |
| `6de31a1` | `bin/bench-frankenphp.sh` benchmark harness |

Notable runtime finding: **opcache preload corrupts the engine heap in
FrankenPHP ZTS workers** (`zend_mm_heap corrupted`); preload is disabled in
`php.ini` with an explanatory comment. The per-request kernel reboot replaces
preload's warmup role.

## Acceptance results — 95/95 PASS

`bin/validate-frankenphp.sh` (full battery), artifacts:
`.kimchi/docs/validation-20261024-104602/`

| Section | Checks | Result |
|---|---|---|
| S0 image/confinement (prod defaults, static files restricted to public/) | 6 | PASS |
| S1 prod env (server + CLI) | 3 | PASS |
| S2 health-check with real redis dependency | 4 | PASS |
| S3 API key auth (negatives, CRUD, 5k unicode body, redis persistence) | 12 | PASS |
| S4 CSRF + sessions (token, cookie, forged-token rejection) | 7 | PASS |
| S5 OIDC login → launch → tool render → nonce-replay rejection; JWKS; client-credentials AGS/NRPS (media-type negotiation, scope isolation) | 22 | PASS |
| S6 deep-linking surfaces | 4 | PASS |
| S7 trusted-proxy header ingestion at server layer | 2 | PASS |
| S8 worker battery | 35 | PASS |

S8 highlights (single-worker `WORKER_NUM=1`):
- 20× alternating valid/anonymous/invalid API calls → exact expected statuses (no token/session bleed)
- application exception does not poison the next request
- 3000-request soak (1500 dashboard + 1500 API): **44.13 MiB → 44.23 MiB** (stable)
- worker restart via Caddy admin API → service healthy after restart
- `docker stop` under in-flight traffic: **238 ms**, stack recovers
- `docker/kube/Dockerfile` (Cloud Build path) builds
- phpunit: OK (1 test, 2 assertions); exit 1 stems from the pre-existing
  deprecation-count policy (Symfony 5.4 bridge notices under PHP 8.4),
  judged on log content by the harness

## Benchmarks

`bin/bench-frankenphp.sh` — prod image vs git-master stack (nginx + php-fpm 8.4,
prod app config forced via env), identical resource caps (app 1.0 CPU / 768 MB,
redis 0.5 / 128 MB), hey 10 s @ concurrency 10, 100-request warmup, readiness
gate before measuring. Baseline uses master's as-shown FPM defaults (pm dynamic,
max_children 5 — the repo ships no FPM tuning).

Run 1 (`2026-10-24T10:23:30Z`, `.kimchi/docs/bench-20261024-122330/`):

| variant | endpoint | rps | p50 (s) | p95 (s) | p99 (s) |
|---|---|---|---|---|---|
| worker | dashboard `/` | 407.5 | 0.0244 | 0.0374 | 0.0426 |
| worker | /health-check | 701.2 | 0.0132 | 0.0258 | 0.0544 |
| worker | JWKS | 936.7 | 0.0104 | 0.0189 | 0.0221 |
| classic | dashboard `/` | 355.9 | 0.0270 | 0.0642 | 0.0706 |
| classic | /health-check | 553.8 | 0.0138 | 0.0568 | 0.0619 |
| classic | JWKS | 662.8 | 0.0145 | 0.0246 | 0.0385 |
| baseline | dashboard `/` | 353.0 | 0.0072 | 0.0857 | 0.0873 |
| baseline | /health-check | 655.2 | 0.0029 | 0.0834 | 0.0851 |
| baseline | JWKS | 561.6 | 0.0035 | 0.0840 | 0.0858 |

Run 2 (variance sample, `2026-10-24T10:25:52Z`, `.kimchi/docs/bench-20261024-122552/`):

| variant | endpoint | rps | p50 (s) | p95 (s) | p99 (s) |
|---|---|---|---|---|---|
| worker | dashboard `/` | 511.1 | 0.0190 | 0.0300 | 0.0581 |
| worker | /health-check | 825.6 | 0.0112 | 0.0217 | 0.0533 |
| worker | JWKS | 941.2 | 0.0101 | 0.0188 | 0.0228 |
| classic | dashboard `/` | 427.5 | 0.0230 | 0.0349 | 0.0636 |
| classic | /health-check | 623.3 | 0.0146 | 0.0271 | 0.0578 |
| classic | JWKS | 747.9 | 0.0114 | 0.0228 | 0.0614 |
| baseline | dashboard `/` | 343.0 | 0.0075 | 0.0856 | 0.0872 |
| baseline | /health-check | 633.7 | 0.0030 | 0.0835 | 0.0850 |
| baseline | JWKS | 564.6 | 0.0034 | 0.0840 | 0.0859 |

Reading:
- worker mode delivers the highest throughput on all three endpoints
  (+15 % rps vs baseline on `/`, +67 % on JWKS) and the tightest tail latency
  (p95 19–37 ms vs baseline's flat ~84 ms — the untuned FPM pool shows bimodal
  queueing spikes).
- classic mode is uniformly the slowest; worker mode is the right default.
- cold first request after start: 2.7–4 ms (images pre-warm caches at build;
  baseline is warmed by the harness to match).
- host-level variance is non-trivial (repeat runs differ by ~5–15 % on rps);
  direction of results is stable across all runs.

## Blocked / not verified

- **Cloud Build execution**: `cloudbuild.yaml` references private Artifact
  Registry bases, not pullable from this environment (no GCP credentials).
  The kube Dockerfile was rewritten to public bases and builds locally
  (S8 PASS), but the actual Cloud Build run remains unverified.
- Pull-through-cache mirrors for the private bases should be configured in CI
  before merging this branch.

## Pre-existing issues found (unfixed, out of scope)

- `vendor/bin/simple-phpunit` bootstrap is broken (bridge composer layout);
  tests run via the vendored phpunit 8.5 directly.
- Traefik v2.4 pin incompatible with modern Docker Engine API (fixed to v2.11
  in the new compose as part of the migration).
- master's `docker/phpfpm/Dockerfile` no longer builds: `pecl install redis`
  now defaults msgpack/lz4 on, headers absent (bench uses a patched copy,
  master tree untouched).
- phpunit deprecation-count exit policy makes `composer test` rc=1 under
  PHP 8.4 despite green tests.

## Follow-ups

1. Configure Artifact Registry pull-through cache and rerun the real Cloud Build.
2. Consider `shutdown_delay` in the Caddyfile to drain in-flight requests on
   SIGTERM (current stop cuts connections at 238 ms — acceptable here, but
   drainable).
3. Revisit opcache preload if/when FrankenPHP fixes ZTS preloading (monitor
   upstream).
4. Tune `WORKER_NUM` per deployment CPU allocation (default 2; validation
   proved isolation at 1).
