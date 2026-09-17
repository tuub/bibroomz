#!/usr/bin/env bash

# Isolated deployment checks. Requires nginx, redis, acl, curl and node.
# nix shell --inputs-from . nixpkgs#{nginx,redis,acl,curl,nodejs} --command bash review/test-review-app.sh
set -euo pipefail

repo_root="$(cd "$(dirname "$0")/.." && pwd)"
test_root="$(mktemp -d)"
export REVIEW_TEST_REDIS_SOCKET="$test_root/redis.sock"
REVIEW_TEST_REDIS_CLI="$(command -v redis-cli)"
export REVIEW_TEST_REDIS_CLI
export REDISCLI_AUTH=review-test-password
export REVIEW_ROOT="$test_root/review"
export REVIEW_TEST_SYSTEMCTL_LOG="$test_root/systemctl.log"
export REVIEW_TEST_SUDO_LOG="$test_root/sudo.log"
export REVIEW_WEB_GROUP
REVIEW_WEB_GROUP="$(id -g)"
backend_pid=""

cleanup() {
    "$REVIEW_TEST_REDIS_CLI" -s "$REVIEW_TEST_REDIS_SOCKET" shutdown nosave > /dev/null 2>&1 || true
    if [[ -f "$test_root/nginx.pid" ]]; then
        nginx -p "$test_root/" -c "$test_root/nginx.conf" -s quit > /dev/null 2>&1 || true
    fi
    if [[ -n "$backend_pid" ]]; then
        kill "$backend_pid" 2>/dev/null || true
        wait "$backend_pid" 2>/dev/null || true
    fi
    rm --recursive --force "$test_root"
}
trap cleanup EXIT
trap 'cat "$test_root/deploy.log" "$test_root/destroy.log" 2>/dev/null || true' ERR

# Match CI: the entry point arrives on stdin, outside any checkout.
review_app() (
    cd "$test_root"
    bash -s "$@" < "$repo_root/review/review-app.sh"
)

mkdir --parents "$test_root/bin" "$REVIEW_ROOT" \
    "$test_root/source/scripts" "$test_root/source/storage/logs" "$test_root/source/bootstrap/cache" \
    "$test_root/source/public" "$test_root/source/review"

# Only infrastructure commands are replaced; git, ACLs, Redis and nginx are real.
printf '#!/bin/sh\nexit 0\n' > "$test_root/bin/mysql"
cat > "$test_root/bin/sudo" <<'SH'
#!/bin/sh
printf '%s\n' "$*" >> "$REVIEW_TEST_SUDO_LOG"
exit 97
SH
cat > "$test_root/bin/systemctl" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
[[ "$1" == --user ]]
printf '%s\n' "$*" >> "$REVIEW_TEST_SYSTEMCTL_LOG"
shift
case "$1" in
    enable|restart) shift ;;
    disable) [[ "$2" == --now ]]; shift 2 ;;
    *) exit 1 ;;
esac
[[ $# == 3 ]]
for unit in "$@"; do
    [[ "$unit" =~ ^roomz-(reverb|queue|scheduler)@[a-z0-9-]+\.service$ ]]
done
SH
cat > "$test_root/bin/redis-cli" <<'SH'
#!/bin/sh
exec "$REVIEW_TEST_REDIS_CLI" -s "$REVIEW_TEST_REDIS_SOCKET" "$@"
SH
chmod +x "$test_root/bin/"*
export PATH="$test_root/bin:$PATH"

cat > "$REVIEW_ROOT/review.env" <<'ENV'
REVIEW_BASE_URL=https://review.example.test
REVIEW_DB_USERNAME=review
REVIEW_DB_PASSWORD='review&test ${UNRESOLVED} $(printf injected) `printf injected`'
REVIEW_REDIS_PASSWORD=review-test-password
REVIEW_AUTH_API_ENDPOINT=https://auth.example.test
ENV

# These files must inherit writable ACLs before the deployment build starts.
cat > "$test_root/source/scripts/deploy.sh" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
umask 077
mkdir -p storage/logs/nested
printf 'build\n' >> storage/logs/nested/build.log
printf 'cache\n' > bootstrap/cache/generated.php
getfacl --omit-header --numeric storage/logs/nested/build.log \
    | grep --extended-regexp --quiet "^group:$REVIEW_WEB_GROUP:(rw-|rwx[[:blank:]]+#effective:rw-)$"
SH
chmod +x "$test_root/source/scripts/deploy.sh"
cp "$repo_root/review/review.env.template" "$test_root/source/review/"
touch "$test_root/source/storage/logs/.gitkeep" "$test_root/source/bootstrap/cache/.gitkeep" \
    "$test_root/source/public/index.php"
git -C "$test_root/source" init --quiet
git -C "$test_root/source" add .
git -C "$test_root/source" -c user.name=Review -c user.email=review@example.test commit --quiet -m fixture
export REVIEW_REPOSITORY_URL="$test_root/source"
REVIEW_COMMIT_SHA="$(git -C "$test_root/source" rev-parse HEAD)"
export REVIEW_COMMIT_SHA

redis-server --port 0 --unixsocket "$REVIEW_TEST_REDIS_SOCKET" --save '' --appendonly no \
    --requirepass "$REDISCLI_AUTH" --daemonize yes --pidfile "$test_root/redis.pid" --logfile "$test_root/redis.log"
for ((attempt = 0; attempt < 100; attempt++)); do
    if redis-cli ping > /dev/null 2>&1; then break; fi
    sleep 0.05
done
redis-cli ping > /dev/null

# Reserve three adjacent free ports before deployments; use real HTTP and
# upgrade responses so nginx's upstream path and query forwarding are tested.
cat > "$test_root/backend.mjs" <<'JS'
import http from 'node:http';
import fs from 'node:fs';
for (let first = 6100; first <= 6197; first++) {
    const servers = [];
    try {
        for (let port = first; port < first + 3; port++) {
            const server = http.createServer((req, res) => res.end(`${port}:${req.url}`));
            server.on('upgrade', (req, socket) => {
                socket.end(`HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n\r\n${port}:${req.url}`);
            });
            await new Promise((resolve, reject) => {
                server.once('error', reject);
                server.listen(port, '127.0.0.1', resolve);
            });
            servers.push(server);
        }
        fs.writeFileSync(process.argv[2], String(first));
        break;
    } catch (error) {
        await Promise.all(servers.map(server => new Promise(resolve => server.close(resolve))));
        if (error.code !== 'EADDRINUSE' || first === 6197) throw error;
    }
}
JS
node "$test_root/backend.mjs" "$test_root/first-port" > "$test_root/backend.log" 2>&1 &
backend_pid=$!
for ((attempt = 0; attempt < 100; attempt++)); do
    if [[ -f "$test_root/first-port" ]]; then break; fi
    if ! kill -0 "$backend_pid" 2>/dev/null; then cat "$test_root/backend.log"; exit 1; fi
    sleep 0.05
done
REVIEW_PORT_MIN="$(cat "$test_root/first-port")"
REVIEW_PORT_MAX=$(( REVIEW_PORT_MIN + 2 ))
export REVIEW_PORT_MIN REVIEW_PORT_MAX

for slug in default include feature-demo; do
    REVIEW_SLUG="$slug" review_app deploy > "$test_root/deploy.log" 2>&1
    app_dir="$REVIEW_ROOT/apps/$slug"
    first_secrets="$(sed -n '/^APP_KEY=/p; /^REVERB_APP_SECRET=/p' "$app_dir/.env")"
    first_port="$(cat "$REVIEW_ROOT/ports/$slug")"
    grep --fixed-strings --line-regexp --quiet "VITE_REVERB_PATH=\"/review/$slug/reverb/$first_port\"" "$app_dir/.env"
    # Password text must survive substitution without being expanded or executed.
    # shellcheck disable=SC2016
    grep --fixed-strings --line-regexp --quiet \
        'DB_PASSWORD="review&test ${UNRESOLVED} $(printf injected) `printf injected`"' "$app_dir/.env"

    getfacl --absolute-names --omit-header --numeric "$app_dir/storage/logs/nested/build.log" \
        | grep --quiet "^group:$REVIEW_WEB_GROUP:rw-$"
    getfacl --absolute-names --omit-header --numeric "$app_dir/storage/logs/nested" \
        | grep --quiet "^default:user:$(id -u):rwx$"

    REVIEW_SLUG="$slug" review_app deploy > "$test_root/deploy.log" 2>&1
    [[ "$(sed -n '/^APP_KEY=/p; /^REVERB_APP_SECRET=/p' "$app_dir/.env")" == "$first_secrets" ]]
    [[ "$(cat "$REVIEW_ROOT/ports/$slug")" == "$first_port" ]]
done
printf 'PASS: deploy over stdin, redeploy, inherited ACLs and stable secrets/ports\n'

# Use the actual websocket location with a 404 fallback for other paths.
{
    printf 'pid %s/nginx.pid;\nerror_log %s/nginx.log;\nevents {}\nhttp {\naccess_log off;\n' "$test_root" "$test_root"
    printf 'client_body_temp_path client; proxy_temp_path proxy; fastcgi_temp_path fastcgi; uwsgi_temp_path uwsgi; scgi_temp_path scgi;\n'
    sed '/^server {/,$d' "$repo_root/deployment/nginx.conf"
    printf 'include %s/review/nginx/http.conf;\n' "$repo_root"
    printf 'server { server_name review.example.test; listen unix:%s/nginx.sock;\n' "$test_root"
    sed -n '/^    location ~ .*review_reverb_port/,/^    }/p' "$repo_root/review/nginx/server.conf"
    printf 'location / { return 404; }\n}\n}\n'
} > "$test_root/nginx.conf"
nginx -p "$test_root/" -c "$test_root/nginx.conf" -e "$test_root/nginx.log"
for slug in default include feature-demo; do
    port="$(cat "$REVIEW_ROOT/ports/$slug")"
    url="http://localhost/review/$slug/reverb/$port/app/review-$slug?protocol=7&client=js"
    actual="$(curl --silent --show-error --fail --max-time 5 --unix-socket "$test_root/nginx.sock" "$url")"
    [[ "$actual" == "$port:/app/review-$slug?protocol=7&client=js" ]]
    actual="$(curl --silent --show-error --fail --max-time 5 --unix-socket "$test_root/nginx.sock" \
        -H 'Connection: Upgrade' -H 'Upgrade: websocket' "$url")"
    [[ "$actual" == "$port:/app/review-$slug?protocol=7&client=js" ]]
done
for path in /reverb/6001/app/key /reverb/6200/app/key /reverb/6379/app/key /reverb/6100/apps/key/events; do
    status="$(curl --silent --show-error --max-time 5 --output /dev/null --write-out '%{http_code}' \
        --unix-socket "$test_root/nginx.sock" "http://localhost/review/default$path")"
    [[ "$status" == 404 ]]
done
printf 'PASS: nginx forwards websocket paths, queries and upgrades only within the reserved range\n'

for database in 0 1; do
    redis-cli -n "$database" set review_default_database_key value > /dev/null
    redis-cli -n "$database" set 'review_default_cache_key with spaces' value > /dev/null
    redis-cli -n "$database" set review_include_database_key keep > /dev/null
done
REVIEW_SLUG=default review_app destroy > "$test_root/destroy.log" 2>&1
for database in 0 1; do
    [[ "$(redis-cli -n "$database" exists review_default_database_key 'review_default_cache_key with spaces')" == 0 ]]
    [[ "$(redis-cli -n "$database" get review_include_database_key)" == keep ]]
done
[[ ! -e "$REVIEW_ROOT/apps/default" && ! -L "$REVIEW_ROOT/www/review/default" ]]
[[ ! -e "$REVIEW_ROOT/ports/default" ]]
printf 'PASS: teardown removes both Redis databases and retains other apps\n'

REVIEW_SLUG=default review_app destroy > "$test_root/destroy.log" 2>&1
printf 'PASS: teardown works without a checkout\n'

[[ "$(grep --count '^--user enable ' "$REVIEW_TEST_SYSTEMCTL_LOG")" == 6 ]]
[[ "$(grep --count '^--user restart ' "$REVIEW_TEST_SYSTEMCTL_LOG")" == 6 ]]
[[ "$(grep --count '^--user disable --now ' "$REVIEW_TEST_SYSTEMCTL_LOG")" == 2 ]]
[[ ! -e "$REVIEW_TEST_SUDO_LOG" ]]
if REVIEW_SLUG=invalid-range REVIEW_PORT_MIN=6001 review_app deploy > /dev/null 2>&1; then
    printf 'FAIL: deployed outside the reserved port range\n' >&2
    exit 1
fi
printf 'PASS: deploy and teardown use only user services and reject unsupported ports\n'
