#!/usr/bin/env bash
#
# Benchmark harness for the FrankenPHP migration.
#
# Compares, under identical resource caps (app: 1.0 CPU / 768M, redis: 0.5 CPU / 128M):
#   - worker:   production image, FrankenPHP worker mode (this branch, default)
#   - classic:  production image, FrankenPHP classic php_server mode
#   - baseline: git-master Nginx + PHP-FPM stack (worktree of master),
#               app configuration forced to prod to isolate the runtime variable
#
# Usage: bin/bench-frankenphp.sh [worker|classic|baseline]...
#
# Results: markdown summary on stdout + raw hey logs in .kimchi/docs/bench-<ts>/
set -uo pipefail

cd "$(dirname "$0")/.."
REPO="$PWD"
DURATION="${DURATION:-10s}"
CONC="${CONC:-10}"
URLS=(
    "/"
    "/health-check"
    "/lti1p3/.well-known/jwks/platformSet.json"
)
URL_TAGS=( "dashboard" "health" "jwks" )

OUT=".kimchi/docs/bench-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$OUT"
SUMMARY="$OUT/summary.md"
log()  { printf '%s\n' "$*" | tee -a "$SUMMARY" >&2; }

PHASES=("$@")
[ ${#PHASES[@]} -eq 0 ] && PHASES=(worker classic baseline)

hey_run() { # hey_run <port> <path> <outfile>
    docker run --rm --network host williamyeh/hey -z "$DURATION" -c "$CONC" \
        "http://localhost:$1$2" >"$3" 2>&1
}

hey_status_ok() { # hey_status_ok <outfile> -> 0 if every response was 2xx
    # hey reports the status-code distribution; any 4xx/5xx row invalidates
    # the throughput/latency numbers for that endpoint
    ! grep -qE '^\s+\[[45][0-9]{2}\]' "$1"
}

hey_first() { # hey_first <port> <path> -> first successful request total time
    curl -sS -o /dev/null --max-time 30 -w '%{time_total}' "http://localhost:$1$2"
}

wait_ready() { # wait_ready <port> -> block until / answers 200 (max 30s)
    local n
    for n in $(seq 1 60); do
        [ "$(curl -sS -o /dev/null --max-time 2 -w '%{http_code}' "http://localhost:$1/" 2>/dev/null)" = "200" ] && return 0
        sleep 0.5
    done
    return 1
}

FAILED=0

parse_hey() { # parse_hey <file> -> "rps p50 p95 p99"
    awk '
        /Requests\/sec/ { rps=$2 }
        /^[[:space:]]+50% in/ { p50=$3 }
        /^[[:space:]]+95% in/ { p95=$3 }
        /^[[:space:]]+99% in/ { p99=$3 }
        END { printf "%.1f %s %s %s", rps, p50, p95, p99 }
    ' "$1"
}

mem_record() { # mem_record <project> <phase>
    docker stats --no-stream --format '{{.Name}} {{.MemUsage}} {{.CPUPerc}}' \
        | grep -E "devkit-bench|bench" > "$OUT/mem-$1.txt" 2>/dev/null || true
}

teardown() { # teardown <project>
    docker compose -p "$1" down -v --remove-orphans >/dev/null 2>&1 || true
}

# ----------------------------------------------------------------- worker
phase_worker() {
    local port=8097 project=devkit-bench-worker
    log "### worker (FrankenPHP worker mode, prod image)"
    docker build -q -t devkit-lti1p3-frankenphp:bench -f docker/frankenphp/Dockerfile . >/dev/null || { log "BUILD FAIL worker image"; return 1; }
    BENCH_PORT=$port BENCH_WORKER_NUM="${BENCH_WORKER_NUM:-2}" \
        docker compose -p "$project" -f docker/bench/docker-compose.bench.yml up -d --wait >/dev/null 2>&1 \
        || { log "UP FAIL worker"; teardown "$project"; return 1; }
    wait_ready "$port" || { log "readiness timeout"; return 1; }
    COLD=$(hey_first "$port" "/")
    log "cold first request (s): $COLD"
    docker run --rm --network host williamyeh/hey -n 100 -c 4 "http://localhost:$port/" >/dev/null 2>&1
    local i
    for i in "${!URLS[@]}"; do
        hey_run "$port" "${URLS[$i]}" "$OUT/worker-${URL_TAGS[$i]}.txt" || { log "hey failed ${URL_TAGS[$i]}"; FAILED=$((FAILED+1)); continue; }
        if hey_status_ok "$OUT/worker-${URL_TAGS[$i]}.txt"; then
            log "| worker | ${URL_TAGS[$i]} | $(parse_hey "$OUT/worker-${URL_TAGS[$i]}.txt") |"
        else
            FAILED=$((FAILED+1))
            log "| worker | ${URL_TAGS[$i]} | INVALID — non-2xx responses under load (see raw log) |"
        fi
    done
    mem_record worker
    teardown "$project"
}

# ---------------------------------------------------------------- classic
phase_classic() {
    local port=8098 project=devkit-bench-classic
    log "### classic (FrankenPHP php_server, prod image)"
    docker build -q -t devkit-lti1p3-frankenphp:bench -f docker/frankenphp/Dockerfile . >/dev/null || { log "BUILD FAIL worker image"; return 1; }
    BENCH_PORT=$port \
        docker compose -p "$project" -f docker/bench/docker-compose.bench.yml -f docker/bench/docker-compose.bench-classic.yml up -d --wait >/dev/null 2>&1 \
        || { log "UP FAIL classic"; teardown "$project"; return 1; }
    wait_ready "$port" || { log "readiness timeout"; return 1; }
    COLD=$(hey_first "$port" "/")
    log "cold first request (s): $COLD"
    docker run --rm --network host williamyeh/hey -n 100 -c 4 "http://localhost:$port/" >/dev/null 2>&1
    local i
    for i in "${!URLS[@]}"; do
        hey_run "$port" "${URLS[$i]}" "$OUT/classic-${URL_TAGS[$i]}.txt" || { log "hey failed ${URL_TAGS[$i]}"; FAILED=$((FAILED+1)); continue; }
        if hey_status_ok "$OUT/classic-${URL_TAGS[$i]}.txt"; then
            log "| classic | ${URL_TAGS[$i]} | $(parse_hey "$OUT/classic-${URL_TAGS[$i]}.txt") |"
        else
            FAILED=$((FAILED+1))
            log "| classic | ${URL_TAGS[$i]} | INVALID — non-2xx responses under load (see raw log) |"
        fi
    done
    mem_record classic
    teardown "$project"
}

# --------------------------------------------------------------- baseline
phase_baseline() {
    local port=8096 project=devkit-bench-baseline
    log "### baseline (git master: nginx + php-fpm 8.4, prod app config)"
    local WT=/tmp/devkit-bench-master
    rm -rf "$WT" 2>/dev/null \
        || docker run --rm -v /tmp:/hosttmp alpine rm -rf /hosttmp/devkit-bench-master
    git worktree prune
    git worktree add "$WT" master >/dev/null 2>&1 || { log "WORKTREE FAIL"; return 1; }
    cp -a vendor "$WT/vendor"
    docker build -q -t devkit-baseline-phpfpm:bench -f docker/tooling/Dockerfile.baseline-phpfpm "$WT/docker/phpfpm" >/dev/null || { log "BUILD FAIL baseline image"; return 1; }
    BASELINE_DIR=$WT \
        docker compose -p "$project" -f docker/bench/docker-compose.bench-baseline.yml up -d --wait >/dev/null 2>&1 \
        || { log "UP FAIL baseline"; teardown "$project"; return 1; }
    # warm the prod cache once (mirrors the build-time cache warmup of the prod image)
    docker compose -p "$project" -f docker/bench/docker-compose.bench-baseline.yml \
        exec -T devkit_lti1p3_phpfpm php bin/console cache:warmup --env=prod >/dev/null 2>&1 \
        || log "baseline cache warmup failed (first request will pay it)"
    wait_ready "$port" || { log "readiness timeout"; return 1; }
    COLD=$(hey_first "$port" "/")
    log "cold first request (s): $COLD"
    docker run --rm --network host williamyeh/hey -n 100 -c 4 "http://localhost:$port/" >/dev/null 2>&1
    local i
    for i in "${!URLS[@]}"; do
        hey_run "$port" "${URLS[$i]}" "$OUT/baseline-${URL_TAGS[$i]}.txt" || { log "hey failed ${URL_TAGS[$i]}"; FAILED=$((FAILED+1)); continue; }
        if hey_status_ok "$OUT/baseline-${URL_TAGS[$i]}.txt"; then
            log "| baseline | ${URL_TAGS[$i]} | $(parse_hey "$OUT/baseline-${URL_TAGS[$i]}.txt") |"
        else
            FAILED=$((FAILED+1))
            log "| baseline | ${URL_TAGS[$i]} | INVALID — non-2xx responses under load (see raw log) |"
        fi
    done
    mem_record baseline
    teardown "$project"
    git worktree remove --force "$WT" >/dev/null 2>&1 || true
}

log "# FrankenPHP benchmarks ($(date -u +%FT%TZ))"
log "duration=$DURATION concurrency=$CONC app-caps: cpus=1.0 mem=768M redis: 0.5/128M"
log ""
log "| variant | endpoint | rps | p50 (s) | p95 (s) | p99 (s) |"
log "|---|---|---|---|---|---|"

P=""
for P in "${PHASES[@]}"; do
    if ! "phase_$P"; then
        FAILED=$((FAILED+1))
        log "PHASE FAILED: $P"
    fi
done

log ""
log "raw logs: $OUT"
exit "$FAILED"
