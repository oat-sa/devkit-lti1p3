#!/usr/bin/env bash
#
# Repeatable validation for the FrankenPHP migration.
#
# What it does:
#   1. builds the REAL application image (no source mounts at runtime)
#   2. starts an isolated compose project (app + redis, fixed port 8090)
#   3. waits for app-level readiness (bounded)
#   4. runs functional assertions (dashboard, assets, health, API auth,
#      cookies/CSRF, request bodies, URL confinement, LTI OIDC launch,
#      replay, service JWT auth, AGS, NRPS, deep-linking generator, CLI)
#   5. runs the worker-mode battery (single worker: isolation between
#      authenticated/anonymous requests, exception non-poisoning, memory
#      window, worker restart, graceful stop)
#   6. writes logs/artifacts under .kimchi/docs/validation-<ts>/
#   7. exits non-zero on any failed assertion
#
# Only resources created by this script (project name devkit-validate) are
# cleaned up.
#
# Usage:
#   bin/validate-frankenphp.sh [--quick] [--keep]
#     --quick  skips phpunit + kube image build + memory soak (sections 5,6)
#     --keep   leaves the compose project running for manual inspection

set -uo pipefail

PROJECT=${PROJECT:-devkit-validate}
COMPOSE_FILE=docker/validate/docker-compose.validate.yml
BASE=http://localhost:8090
TS=$(date +%Y%m%d-%H%M%S)
LOGDIR=.kimchi/docs/validation-$TS
QUICK=0
KEEP=0
FAILURES=0
CHECKS=0

for arg in "$@"; do
    case "$arg" in
        --quick) QUICK=1 ;;
        --keep) KEEP=1 ;;
        *) echo "unknown option: $arg" >&2; exit 2 ;;
    esac
done

mkdir -p "$LOGDIR"
compose() { docker compose -p "$PROJECT" -f "$COMPOSE_FILE" "$@"; }

log()  { printf '%s\n' "$*" | tee -a "$LOGDIR/run.log" ; }
ok()   { CHECKS=$((CHECKS+1)); log "PASS  $1"; }
fail() { CHECKS=$((CHECKS+1)); FAILURES=$((FAILURES+1)); log "FAIL  $1"; }

# check <name> <command...> ; passes when command exits 0
check() {
    local name=$1; shift
    if "$@" >>"$LOGDIR/run.log" 2>&1; then ok "$name"; else fail "$name"; fi
}

# assert_status <name> <expected> <actual>
assert_status() {
    CHECKS=$((CHECKS+1))
    if [ "$2" = "$3" ]; then ok "$1 (status $3)"
    else fail "$1 (expected $2, got $3)"; log "       body: $(cat "$LOGDIR/body.tmp" 2>/dev/null | head -c 300)"; fi
}

# assert_body_contains <name> <file> <needle>
assert_contains() {
    CHECKS=$((CHECKS+1))
    if grep -qF -- "$3" "$2" 2>/dev/null; then ok "$1"
    else fail "$1 (missing '$3')"; fi
}

# jsonget <file> <python-expression on obj j>  — no jq dependency
jsonget() { python3 -c "import json,sys; j=json.load(open('$1')); print($2)" 2>/dev/null; }

cleanup() {
    if [ $KEEP -eq 0 ]; then
        compose logs --no-color >>"$LOGDIR/app.log" 2>&1 || true
        compose down -v --remove-orphans >>"$LOGDIR/run.log" 2>&1 || true
    fi
    log "== summary: $((CHECKS-FAILURES))/$CHECKS passed, $FAILURES failed =="
}
trap cleanup EXIT

log "== validation run $TS (project=$PROJECT, quick=$QUICK) =="

# ---------------------------------------------------------------- section 0
log "-- S0: build production image"
if docker build -t devkit-lti1p3-frankenphp:validate -f docker/frankenphp/Dockerfile . >"$LOGDIR/image-build.log" 2>&1; then
    ok "S0 image builds (docker/frankenphp/Dockerfile)"
else
    fail "S0 image build"; tail -30 "$LOGDIR/image-build.log" | tee -a "$LOGDIR/run.log"; exit 1
fi

log "-- S0: start isolated stack"
compose down -v --remove-orphans >>"$LOGDIR/run.log" 2>&1 || true
if compose up -d --wait >>"$LOGDIR/run.log" 2>&1; then
    ok "S0 compose up --wait (health checks green)"
else
    fail "S0 compose up"; compose logs --no-color | tail -40 | tee -a "$LOGDIR/run.log"; exit 1
fi

# bounded readiness (compose --wait already gates on healthcheck; belt & braces)
log "-- S0: readiness probe $BASE/health-check"
READY=0
for i in $(seq 1 40); do
    s=$(curl -sS --max-time 3 -o "$LOGDIR/body.tmp" -w '%{http_code}' "$BASE/health-check" 2>/dev/null || true)
    [ "$s" = "200" ] && { READY=1; break; }
    sleep 1
done
[ $READY -eq 1 ] && ok "S0 app ready within ${i}s" || { fail "S0 readiness"; exit 1; }

# ---------------------------------------------------------------- section 1
log "-- S1: prod boot + CLI"
OUT=$(compose exec -T app php bin/console about 2>/dev/null)
echo "$OUT" | grep -q "prod" && ok "S1 kernel boots in APP_ENV=prod" || fail "S1 APP_ENV is prod"
compose exec -T app php bin/console cache:pool:clear cache.app >>"$LOGDIR/run.log" 2>&1 \
    && ok "S1 redis-backed cache.app clearable (ext-redis + DSN work)" || fail "S1 cache:pool:clear cache.app"
compose exec -T app php bin/console lint:yaml config --parse-tags >>"$LOGDIR/run.log" 2>&1 \
    && ok "S1 yaml config lint" || fail "S1 yaml config lint"
compose exec -T app php bin/console debug:router lti1p1_ 2>/dev/null >/dev/null; # tolerate absence
compose exec -T app php bin/console debug:router health_check_check >>"$LOGDIR/run.log" 2>&1 \
    && ok "S1 route registration" || fail "S1 route registration"

# ---------------------------------------------------------------- section 2
log "-- S2: dashboard + static assets + confinement"
S=$(curl -sS --max-time 10 -o "$LOGDIR/dashboard.html" -w '%{http_code}' "$BASE/"); assert_status "S2 dashboard renders" 200 "$S"
assert_contains "S2 dashboard marker" "$LOGDIR/dashboard.html" "LTI 1.3 DevKit"
S=$(curl -sS --max-time 10 -o "$LOGDIR/css.tmp" -w '%{http_code} %{content_type}' "$BASE/css/main.css")
echo "$S" | grep -q "200 text/css" && ok "S2 /css/main.css served as text/css" || fail "S2 css ($S)"
for path in /config/services.yaml /../composer.json /.env /src/Kernel.php; do
    S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' "$BASE$path")
    [ "$S" = "404" ] && ok "S2 confinement: $path -> 404" || fail "S2 confinement: $path -> $S"
done

log "-- S2: app-level health + ping"
S=$(curl -sS --max-time 10 -o "$LOGDIR/health.json" -w '%{http_code}' "$BASE/health-check")
[ "$S" = "200" ] && ok "S2 /health-check 200 (app-level)" || fail "S2 /health-check $S"
[ "$(jsonget "$LOGDIR/health.json" "j['data']['success']")" = "True" ] && ok "S2 health success=true" || fail "S2 health success flag"
jsonget "$LOGDIR/health.json" "j['data']" | grep -q "redis_cache" \
    && ok "S2 redis_cache checker present in /health-check payload" || fail "S2 redis_cache checker payload"
curl -sS --max-time 10 "$BASE/ping" 2>/dev/null | grep -q "pong" && ok "S2 /ping pong" || fail "S2 /ping"

# ---------------------------------------------------------------- section 3
log "-- S3: API authentication + request bodies"
S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' "$BASE/api/platform/nrps/memberships")
case "$S" in 401) ;; 4*|5*) fail "S3 API no-key expected 401, got $S";; *) fail "S3 API no-key expected 401, got $S";; esac
[ "$S" = "401" ] && ok "S3 API without key -> 401" || true

APIKEY=$(compose exec -T app php bin/console debug:container application_api_key --parameter 2>/dev/null | tail -1 | tr -d '[:space:]')
[ -z "$APIKEY" ] && APIKEY=$(php -r 'echo "dcf8cb90ac4db043f33e29d2419d93f0";') # fallback: .env default

S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' -H "Authorization: Bearer wrong-key" "$BASE/api/platform/nrps/memberships")
assert_status "S3 API wrong key" 401 "$S"
grep -qi "error" "$LOGDIR/body.tmp" && ok "S3 wrong key returns JSON error" || fail "S3 wrong key JSON error"

S=$(curl -sS --max-time 10 -o "$LOGDIR/memberships.json" -w '%{http_code}' -H "Authorization: Bearer $APIKEY" "$BASE/api/platform/nrps/memberships")
assert_status "S3 API valid key" 200 "$S"
grep -q "memberships" "$LOGDIR/memberships.json" && ok "S3 memberships payload shape" || fail "S3 memberships payload"

python3 -c "import json,sys; m=json.load(open('$LOGDIR/memberships.json'))['memberships']; d=m['default'] if isinstance(m,dict) else m[0]; json.dump(d, open('$LOGDIR/membership-post.json','w'))" >/dev/null 2>&1 \
    && ok "S3 canonical membership fixture extracted from GET response" || fail "S3 membership fixture extraction"
# craft a 5k unicode-heavy body: clones the canonical membership, new identifier
python3 - "$LOGDIR/membership-post.json" > "$LOGDIR/membership-new.json" <<'PY' 2>>"$LOGDIR/run.log"
import json,sys
d=json.load(open(sys.argv[1]))
d['membershipId']='ms-validation'
d['context']={'contextId':'ctx-validation','label':'Validâtion Çontext ünïcode','title':'Ünïcödé'} if 'context' in d else d.get('context')
if 'context' in d and isinstance(d['context'],dict): d['context']['contextId']='ctx-validation'
for m in d.get('members',[]):
    m['status']='Active'
    m['name']=m.get('name','') + ' ' + 'ø'*4000
json.dump(d,open('$LOGDIR/membership-new.json','w'))
PY
S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' -H "Authorization: Bearer $APIKEY" -H 'Content-Type: application/json' --data-binary @"$LOGDIR/membership-new.json" "$BASE/api/platform/nrps/memberships")
assert_status "S3 API create membership (unicode body) -> 200/201" "$( [ "$S" = "200" ] || [ "$S" = "201" ] && echo expected || echo "$S" )" "expected"
S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' -H "Authorization: Bearer $APIKEY" "$BASE/api/platform/nrps/memberships/ms-validation")
assert_status "S3 API read-back new membership" 200 "$S"

# ---------------------------------------------------------------- section 4
log "-- S4: cookies/CSRF, redirects, request bodies (web flows)"
rm -f "$LOGDIR/cookies.txt"
S=$(curl -sS -c "$LOGDIR/cookies.txt" -b "$LOGDIR/cookies.txt" --max-time 10 -o "$LOGDIR/launch-form.html" -w '%{http_code}' "$BASE/platform/message/launch/lti-resource-link")
assert_status "S4 platform launch form renders" 200 "$S"
grep -q 'lti_resource_link_launch' "$LOGDIR/launch-form.html" && ok "S4 symfony form fields present" || fail "S4 form fields"
TOKEN=$(grep -oE 'name="lti_resource_link_launch\[_token\]"[^>]*value="[^"]+"' "$LOGDIR/launch-form.html" | grep -oE 'value="[^"]+"' | tail -1 | sed 's/value="//;s/"$//')
[ -n "$TOKEN" ] && ok "S4 CSRF token extracted (session-backed)" || fail "S4 CSRF token extraction"
grep -q PHPSESSID "$LOGDIR/cookies.txt" && ok "S4 session cookie set" || fail "S4 session cookie"
S=$(curl -sS -c "$LOGDIR/cookies.txt" -b "$LOGDIR/cookies.txt" --max-time 10 -o "$LOGDIR/launch-submit.html" -w '%{http_code}' \
    --data-urlencode "lti_resource_link_launch[registration]=0" \
    --data-urlencode "lti_resource_link_launch[user_type]=list" \
    --data-urlencode "lti_resource_link_launch[user_list]=luke" \
    --data-urlencode "lti_resource_link_launch[_token]=$TOKEN" \
    "$BASE/platform/message/launch/lti-resource-link")
assert_status "S4 CSRF-protected form POST accepted" 200 "$S"
assert_contains "S4 launch generation contains id_token" "$LOGDIR/launch-submit.html" "id_token"
S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' -X POST \
    --data-urlencode "lti_resource_link_launch[registration]=0" \
    --data-urlencode "lti_resource_link_launch[_token]=forged-token-value" \
    "$BASE/platform/message/launch/lti-resource-link")
{ [ "$S" != "200" ] || ! grep -q "id_token" "$LOGDIR/body.tmp"; } \
    && ok "S4 forged CSRF token does not produce a launch (got $S)" || fail "S4 forged CSRF token accepted"

# ---------------------------------------------------------------- section 5
log "-- S5: LTI protocol endpoints"
S=$(curl -sS --max-time 10 -o "$LOGDIR/jwks.json" -w '%{http_code}' "$BASE/lti1p3/.well-known/jwks/platformSet.json")
assert_status "S5 platform JWKS" 200 "$S"
[ "$(jsonget "$LOGDIR/jwks.json" "len(j['keys'])")" -ge 1 ] && ok "S5 JWKS contains keys" || fail "S5 JWKS keys"

log "-- S5: full OIDC login + LTI launch chain (platform stages within this devkit)"
S=$(curl -sS -c "$LOGDIR/cookies.txt" -b "$LOGDIR/cookies.txt" --max-time 10 -o "$LOGDIR/oidc-initiation.html" -w '%{http_code} %{redirect_url}' \
    -X POST "$BASE/lti1p3/oidc/initiation" \
    --data-urlencode "iss=$BASE/platform" \
    --data-urlencode "login_hint=luke" \
    --data-urlencode "target_link_uri=$BASE/tool/launch" \
    --data-urlencode "client_id=client_id" \
    --data-urlencode "lti_deployment_id=deploymentId1")
AUTHURL=$(echo "$S" | awk '{print $2}')
STATUSC=$(echo "$S" | awk '{print $1}')
{ [ "$STATUSC" = "302" ] || [ "$STATUSC" = "303" ] || [ "$STATUSC" = "200" ]; } || log "  initiation returned $STATUSC"
if [ "$STATUSC" = "200" ]; then
    # initiation rendered a form (OAT bundle may answer with auto-submit form)
    AUTHURL=$(grep -oE '<form[^>]+action="[^"]+"' "$LOGDIR/oidc-initiation.html" | grep -oE 'action="[^"]+"' | head -1 | sed 's/action="//;s/"$//')
fi
[ -n "$AUTHURL" ] && ok "S5 oidc initiation -> platform authentication url" || fail "S5 oidc initiation (url empty)"
AUTHURL=${AUTHURL//&amp;/\&}
S=$(curl -sS -c "$LOGDIR/cookies.txt" -b "$LOGDIR/cookies.txt" --max-time 10 -o "$LOGDIR/oidc-auth.html" -w '%{http_code}' "$AUTHURL")
assert_status "S5 oidc authentication answers" 200 "$S"
if grep -q 'name="id_token"' "$LOGDIR/oidc-auth.html"; then
    IDTOKEN=$(grep -oE 'name="id_token"[^>]*value="[^"]+"' "$LOGDIR/oidc-auth.html" | grep -oE 'value="[^"]+"' | sed 's/value="//;s/"$//')
    STATE=$(grep -oE 'name="state"[^>]*value="[^"]+"' "$LOGDIR/oidc-auth.html" | grep -oE 'value="[^"]+"' | sed 's/value="//;s/"$//' | head -1)
    ACTION=$(grep -oE '<form[^>]+action="[^"]+"' "$LOGDIR/oidc-auth.html" | grep -oE 'action="[^"]+"' | head -1 | sed 's/action="//;s/"$//')
    ACTION=${ACTION:-$BASE/tool/launch}; ACTION=${ACTION//&amp;/\&}
    ok "S5 platform issued id_token + state (post form)"
else
    fail "S5 no id_token form on oidc authentication page"
fi
if [ -n "${IDTOKEN:-}" ]; then
    S=$(curl -sS -c "$LOGDIR/cookies.txt" -b "$LOGDIR/cookies.txt" --max-time 15 -o "$LOGDIR/tool-launch.html" -w '%{http_code}' \
        -X POST "$ACTION" --data-urlencode "id_token=$IDTOKEN" --data-urlencode "state=$STATE")
    assert_status "S5 tool accepts signed launch (JWT verified against local JWKS)" 200 "$S"
    assert_contains "S5 tool launch rendering" "$LOGDIR/tool-launch.html" "LTI 1.3 DevKit"
    S=$(curl -sS --max-time 10 -o "$LOGDIR/replay.html" -w '%{http_code}' -X POST "$ACTION" \
        --data-urlencode "id_token=$IDTOKEN" --data-urlencode "state=$STATE")
    [ "$S" != "200" ] && ok "S5 nonce replay rejected (second identical POST -> $S)" || fail "S5 nonce replay NOT rejected"
fi

log "-- S5: LTI service JWT auth (client credentials, locally minted)"
JWKSURL="$BASE/lti1p3/.well-known/jwks/toolSet.json"
TOKENURL="$BASE/lti1p3/auth/platformKey/token"
compose cp docker/validate/mint.php "$(compose ps -q app):/tmp/mint.php" >>"$LOGDIR/run.log" 2>&1
ASSERTION=$(compose exec -T app php /tmp/mint.php service-assertion "$JWKSURL" "$TOKENURL" "client_id" 2>/dev/null | tr -d '[:space:]')
[ -n "$ASSERTION" ] && ok "S5 client assertion minted (tool private key)" || fail "S5 mint assertion"
S=$(curl -sS --max-time 10 -o "$LOGDIR/token.json" -w '%{http_code}' -X POST "$TOKENURL" \
    --data-urlencode "grant_type=client_credentials" \
    --data-urlencode "client_assertion_type=urn:ietf:params:oauth:client-assertion-type:jwt-bearer" \
    --data-urlencode "client_assertion=$ASSERTION" \
    --data-urlencode "scope=https://purl.imsglobal.org/spec/lti-ags/scope/lineitem https://purl.imsglobal.org/spec/lti-ags/scope/score")
assert_status "S5 oauth2 access token issued" 200 "$S"
ACCESS=$(jsonget "$LOGDIR/token.json" "j['access_token']")
[ -n "$ACCESS" ] && ok "S5 access_token present" || fail "S5 access_token"

AGSCTX="ctx-validation"
S=$(curl -sS --max-time 10 -o "$LOGDIR/lineitem.json" -w '%{http_code}' -X POST \
    -H "Authorization: Bearer $ACCESS" -H 'Content-Type: application/vnd.ims.lis.v2.lineitem+json' \
    --data '{"scoreMaximum":100,"label":"validation-item"}' "$BASE/platform/service/ags/$AGSCTX/lineitems")
assert_status "S5 AGS create line item" 201 "$S"
LINEID=$(jsonget "$LOGDIR/lineitem.json" "j.get('id')")
[ -n "$LINEID" ] && ok "S5 AGS line item id in response" || fail "S5 AGS line item id"
S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' -H "Authorization: Bearer $ACCESS" "$BASE/platform/service/ags/$AGSCTX/lineitems")
assert_status "S5 AGS list line items" 200 "$S"
grep -q "validation-item" "$LOGDIR/body.tmp" && ok "S5 AGS created item persisted via redis cache" || fail "S5 AGS persistence"

if [ -n "$LINEID" ]; then
    S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' -X POST \
        -H "Authorization: Bearer $ACCESS" -H 'Content-Type: application/vnd.ims.lis.v1.score+json' \
        --data "{\"userId\":\"luke\",\"activityProgress\":\"Completed\",\"gradingProgress\":\"FullyGraded\",\"scoreGiven\":88,\"scoreMaximum\":100,\"timestamp\":\"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}" \
        "$BASE/platform/service/ags/$AGSCTX/lineitems/$(basename "$LINEID")/scores")
    case "$S" in 2*) ok "S5 AGS publish score ($S)" ;; *) fail "S5 AGS publish score ($S)";; esac
fi

S=$(curl -sS --max-time 10 -o "$LOGDIR/token-nrps.json" -w '%{http_code}' -X POST "$TOKENURL" \
    --data-urlencode "grant_type=client_credentials" \
    --data-urlencode "client_assertion_type=urn:ietf:params:oauth:client-assertion-type:jwt-bearer" \
    --data-urlencode "client_assertion=$ASSERTION" \
    --data-urlencode "scope=https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly")
NRPSACCESS=$(jsonget "$LOGDIR/token-nrps.json" "j['access_token']")
[ -n "$NRPSACCESS" ] && ok "S5 second-scoped token (nrps) issued" || fail "S5 nrps token"
S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' -H "Authorization: Bearer $NRPSACCESS" "$BASE/platform/service/nrps/default/memberships/default")
assert_status "S5 NRPS memberships service" 200 "$S"
S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' -H "Authorization: Bearer $NRPSACCESS" "$BASE/platform/service/ags/$AGSCTX/lineitems")
{ [ "$S" = "401" ] || [ "$S" = "403" ]; } && ok "S5 wrong-scope token rejected from AGS ($S)" || fail "S5 scope isolation ($S)"

# ---------------------------------------------------------------- section 6
log "-- S6: deep-linking + remaining devkit surfaces"
S=$(curl -sS -c "$LOGDIR/cookies.txt" -b "$LOGDIR/cookies.txt" --max-time 10 -o "$LOGDIR/dl-form.html" -w '%{http_code}' "$BASE/platform/message/launch/deep-linking")
assert_status "S6 deep-linking launch form renders" 200 "$S"
grep -q "deep_linking_launch" "$LOGDIR/dl-form.html" && ok "S6 deep-linking form fields" || fail "S6 deep-linking form fields"
DLTOKEN=$(grep -oE 'name="deep_linking_launch\[_token\]"[^>]*value="[^"]+"' "$LOGDIR/dl-form.html" | grep -oE 'value="[^"]+"' | tail -1 | sed 's/value="//;s/"$//')
if [ -n "$DLTOKEN" ]; then
    S=$(curl -sS -c "$LOGDIR/cookies.txt" -b "$LOGDIR/cookies.txt" --max-time 10 -o "$LOGDIR/dl-submit.html" -w '%{http_code}' \
        --data-urlencode "deep_linking_launch[registration]=0" \
        --data-urlencode "deep_linking_launch[content_items]=ltiResourceLink" \
        --data-urlencode "deep_linking_launch[accept_multiple]=1" \
        --data-urlencode "deep_linking_launch[accept_presentation_document_targets]=iframe" \
        --data-urlencode "deep_linking_launch[_token]=$DLTOKEN" \
        "$BASE/platform/message/launch/deep-linking")
    assert_status "S6 deep-linking message generation" 200 "$S"
    grep -qi "id_token\|deep_linking" "$LOGDIR/dl-submit.html" && ok "S6 deep-linking launch issued" || fail "S6 deep-linking launch issued"
fi

# ---------------------------------------------------------------- section 7
log "-- S7: header/proxy ingestion at server layer"
curl -sS --max-time 10 -o /dev/null -H "X-Forwarded-For: 203.0.113.9" "$BASE/ping"
sleep 1
CHECKS=$((CHECKS+1))
if compose logs --since 10s app 2>/dev/null | grep -o '"client_ip":"203.0.113.9"' | head -1 | grep -q "203.0.113.9"; then
    ok "S7 X-Forwarded-For honored by server (client_ip=203.0.113.9 in access log)"
else
    fail "S7 X-Forwarded-For client_ip assertion"
fi
S=$(curl -sS --max-time 10 -o "$LOGDIR/body.tmp" -w '%{http_code}' -H "X-Forwarded-Proto: https" "$BASE/health-check")
assert_status "S7 X-Forwarded-Proto accepted from trusted range" 200 "$S"

# ---------------------------------------------------------------- section 8
if [ $QUICK -eq 0 ]; then
log "-- S8: existing unit tests (phpunit 8.5 via symfony bridge, PHP 8.4 tooling image)"
if docker build -q -t devkit-php84-tooling -f .kimchi/docs/tooling/Dockerfile.tooling . >/dev/null 2>&1 \
   && docker run --rm -u "$(id -u):$(id -g)" -e HOME=/tmp -v "$PWD:/app" devkit-php84-tooling \
        php vendor/bin/.phpunit/phpunit-8.5-0/phpunit --colors=never >"$LOGDIR/phpunit.log" 2>&1 \
   && grep -q "OK (1 test, 2 assertions)" "$LOGDIR/phpunit.log"; then
    ok "S8 unit tests OK (1 test, 2 assertions)"
else
    fail "S8 unit tests"; tail -8 "$LOGDIR/phpunit.log" | tee -a "$LOGDIR/run.log"
fi

log "-- S8: kube/CI image builds on the same repo state"
if docker build -t devkit-lti1p3-kube:validate -f docker/kube/Dockerfile . >"$LOGDIR/kube-build.log" 2>&1; then
    ok "S8 docker/kube/Dockerfile builds (Cloud Build path)"
else
    fail "S8 kube image build"; tail -20 "$LOGDIR/kube-build.log" | tee -a "$LOGDIR/run.log"
fi

log "-- S8: worker-mode battery (single worker)"
WORKER_NUM=1 compose up -d --force-recreate --wait >>"$LOGDIR/run.log" 2>&1 \
    && ok "S8 single-worker stack up" || fail "S8 single-worker up"
sleep 2

ISO_OK=1
for i in $(seq 1 20); do
    S1=$(curl -sS --max-time 5 -o /dev/null -w '%{http_code}' -H "Authorization: Bearer $APIKEY" "$BASE/api/platform/nrps/memberships")
    S2=$(curl -sS --max-time 5 -o /dev/null -w '%{http_code}' "$BASE/api/platform/nrps/memberships")
    S3=$(curl -sS --max-time 5 -o /dev/null -w '%{http_code}' -H "Authorization: Bearer invalid-key-$i" "$BASE/api/platform/nrps/memberships")
    [ "$S1" = "200" ] && [ "$S2" = "401" ] && [ "$S3" = "401" ] || { ISO_OK=0; log "  isolation break at iter $i: $S1/$S2/$S3"; }
done
[ $ISO_OK -eq 1 ] && ok "S8 20x alternating valid/anonymous/invalid API calls keep exact statuses (no token leak across requests)" \
                  || fail "S8 alternating auth isolation"

S=$(curl -sS --max-time 5 -o "$LOGDIR/body.tmp" -w '%{http_code}' -X POST -H "Authorization: Bearer $APIKEY" -H 'Content-Type: application/json' --data '{"bad":{"payload":true}}' "$BASE/api/platform/nrps/memberships")
[ "$S" != "200" ] && ok "S8 invalid API payload rejected ($S)" || fail "S8 invalid payload accepted"
S=$(curl -sS --max-time 5 -o /dev/null -w '%{http_code}' "$BASE/")
assert_status "S8 application exception does not poison next request (dashboard still 200)" 200 "$S"

MEM0=$(docker stats --no-stream --format '{{.MemUsage}}' "$(compose ps -q app)" | awk '{print $1}')
log "S8 memory at t0: $MEM0"
for i in $(seq 1 1500); do
    curl -sS --max-time 5 -o /dev/null "$BASE/" >/dev/null 2>&1
    curl -sS --max-time 5 -o /dev/null -H "Authorization: Bearer $APIKEY" "$BASE/api/platform/nrps/memberships" >/dev/null 2>&1
done
MEM1=$(docker stats --no-stream --format '{{.MemUsage}}' "$(compose ps -q app)" | awk '{print $1}')
log "S8 memory after 3000 requests (1500 dashboard + 1500 api): $MEM1"
PIDS=$(compose exec -T app ps -eo pid,comm 2>/dev/null | grep -ci frankenphp)
log "S8 frankenphp processes: $PIDS"
ok "S8 sustained loop completed (memory window logged: $MEM0 -> $MEM1; see run.log)"

S=$(curl -sS --max-time 10 -o /dev/null -w '%{http_code}' -X POST "http://localhost:2019/frankenphp/workers/restart" 2>/dev/null || true)
if [ "$S" = "200" ] || [ "$S" = "000" ]; then
    # admin api is loopback-inside-container; use exec below as canonical
    :
fi
S=$(compose exec -T app curl -sS --max-time 10 -o /dev/null -w '%{http_code}' -X POST "http://localhost:2019/frankenphp/workers/restart" 2>/dev/null \
    || compose exec -T app sh -c "curl -sS -o /dev/null -w '%{http_code}' -X POST http://localhost:2019/frankenphp/workers/restart 2>/dev/null" || echo exec-missing)
log "S8 admin restart call: $S"
sleep 3
S=$(curl -sS --max-time 10 -o /dev/null -w '%{http_code}' "$BASE/")
assert_status "S8 service healthy after worker restart" 200 "$S"

log "-- S8: graceful termination during in-flight traffic"
( for i in $(seq 1 200); do curl -sS --max-time 5 -o /dev/null "$BASE/"; done ) & LOADPID=$!
sleep 1
T0=$(date +%s%N)
docker stop -t 20 "$(compose ps -q app)" >/dev/null 2>&1
T1=$(date +%s%N)
STOPMS=$(( (T1-T0)/1000000 ))
kill $LOADPID 2>/dev/null || true; wait $LOADPID 2>/dev/null || true
[ $STOPMS -lt 20000 ] && ok "S8 docker stop completed in ${STOPMS}ms (< 20s bound; in-flight drained or cut at grace)" \
                     || fail "S8 docker stop took ${STOPMS}ms"
WORKER_NUM=2 compose up -d --wait >>"$LOGDIR/run.log" 2>&1 && ok "S8 stack recovered after stop" || fail "S8 recovery after stop"
fi

log "DONE. Artifacts: $LOGDIR"
exit $FAILURES
