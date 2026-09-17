#!/bin/bash
# Piped over SSH by CI; keep this self-contained until the checkout exists.
set -euo pipefail

fail() { echo "review-app: $*" >&2; exit 1; }

action="${1:-}"
[[ "$action" == deploy || "$action" == destroy ]] || fail "usage: $0 {deploy|destroy}"
: "${REVIEW_ROOT:=/srv/review}" "${REVIEW_SLUG:?}"
[[ "$REVIEW_SLUG" =~ ^[a-z0-9][a-z0-9-]{0,62}$ ]] || fail "invalid slug '$REVIEW_SLUG'"

app_dir="$REVIEW_ROOT/apps/$REVIEW_SLUG"
web_link="$REVIEW_ROOT/www/review/$REVIEW_SLUG"
port_file="$REVIEW_ROOT/ports/$REVIEW_SLUG"
url_path="/review/$REVIEW_SLUG"
units=(roomz-{reverb,queue,scheduler}@"$REVIEW_SLUG".service)
deploy_uid="$(id -u)"
export XDG_RUNTIME_DIR="${XDG_RUNTIME_DIR:-/run/user/$deploy_uid}"

# shellcheck source=/dev/null
source "$REVIEW_ROOT/review.env"
: "${REVIEW_DB_USERNAME:?}" "${REVIEW_DB_PASSWORD:?}"
: "${REVIEW_DB_HOST:=127.0.0.1}" "${REVIEW_REDIS_PASSWORD:=}" "${REVIEW_WEB_GROUP:=www-data}"

# Keep database names stable and below MariaDB's 64-character limit.
db_base="${REVIEW_SLUG//-/_}"
db_hash="$(printf '%s' "$REVIEW_SLUG" | sha1sum | cut -c1-8)"
db_name="review_${db_base:0:40}_$db_hash"

# mktemp creates a private file; credentials never enter mysql's argv or env.
mysql_config="$(mktemp)"
trap 'rm -f "$mysql_config"' EXIT
cat > "$mysql_config" <<EOF
[client]
user="$REVIEW_DB_USERNAME"
password="$REVIEW_DB_PASSWORD"
host="$REVIEW_DB_HOST"
EOF
mysql_exec() { mysql --defaults-file="$mysql_config" --batch --skip-column-names --execute="$1"; }

destroy_app() {
    echo "review-app: removing '$REVIEW_SLUG'"
    systemctl --user disable --now "${units[@]}" || true
    rm -f "$web_link"
    mysql_exec "DROP DATABASE IF EXISTS \`$db_name\`;" || true
    if command -v redis-cli > /dev/null; then
        for database in 0 1; do
            REDISCLI_AUTH="$REVIEW_REDIS_PASSWORD" redis-cli -n "$database" --scan --pattern "review_${REVIEW_SLUG}_*" \
                | xargs --no-run-if-empty --delimiter='\n' -- env REDISCLI_AUTH="$REVIEW_REDIS_PASSWORD" redis-cli -n "$database" del \
                > /dev/null || true
        done
    fi
    rm -f "$port_file"
    rm -rf "$app_dir"
    echo "review-app: '$REVIEW_SLUG' removed"
}

# A subshell releases the registry lock on every return or error.
allocate_port() (
    exec 9>"$REVIEW_ROOT/ports/.lock"
    flock 9
    if [[ -s "$port_file" ]]; then
        port="$(cat "$port_file")"
        [[ "$port" =~ ^61[0-9]{2}$ ]] || fail "registered port '$port' is outside 6100-6199"
        printf '%s\n' "$port"
        return
    fi
    for ((port = REVIEW_PORT_MIN; port <= REVIEW_PORT_MAX; port++)); do
        if ! grep --quiet --line-regexp --fixed-strings "$port" "$REVIEW_ROOT/ports"/* 2>/dev/null; then
            printf '%s\n' "$port" | tee "$port_file"
            return
        fi
    done
    fail "no free Reverb port in $REVIEW_PORT_MIN-$REVIEW_PORT_MAX"
)

# Preserve existing app/encryption secrets when regenerating configuration.
review_secret() {
    local env_file="$1" key="$2" prefix="${3:-}" value=""
    if [[ -f "$env_file" ]]; then
        value="$(sed -n "s/^$key=\"\{0,1\}\([^\"]*\)\"\{0,1\}$/\1/p" "$env_file")"
    fi
    if [[ -n "$value" ]]; then
        printf '%s\n' "$value"
    else
        printf '%s%s\n' "$prefix" "$(head --bytes=32 /dev/urandom | base64)"
    fi
}

# Substitute ${name} references literally; never evaluate template text or values.
render_review_env() {
    local line token name
    while IFS= read -r line || [[ -n "$line" ]]; do
        while [[ "$line" =~ \$\{([a-zA-Z_][a-zA-Z0-9_]*)\} ]]; do
            token="${BASH_REMATCH[0]}" name="${BASH_REMATCH[1]}"
            printf '%s%s' "${line%%"$token"*}" "${!name}"
            line="${line#*"$token"}"
        done
        printf '%s\n' "$line"
    done < "$1"
}

# These locals are read indirectly by render_review_env through the template.
# shellcheck disable=SC2034
write_review_env() {
    local env_file="$1" app_url="$2" db_name="$3" port="$4"
    local url_path="/review/$REVIEW_SLUG" app_key reverb_secret secure_cookie=false
    local auth_method="${REVIEW_AUTH_METHOD:-alma}" test_accounts="${REVIEW_TEST_ACCOUNTS:-false}"
    local mail_host="${REVIEW_MAIL_HOST:-127.0.0.1}" mail_port="${REVIEW_MAIL_PORT:-1025}"
    app_key="$(review_secret "$env_file" APP_KEY base64:)"
    reverb_secret="$(review_secret "$env_file" REVERB_APP_SECRET)"
    if [[ "$app_url" == https://* ]]; then secure_cookie=true; fi

    render_review_env "$app_dir/review/review.env.template" > "$env_file"
    chmod 640 "$env_file"
    chgrp "$REVIEW_WEB_GROUP" "$env_file"
}

# Inherit shared write access; never change metadata on files owned by PHP-FPM.
fix_permissions() {
    find "$app_dir/storage" "$app_dir/bootstrap/cache" -type d -user "$deploy_uid" \
        -exec setfacl --modify "u:$deploy_uid:rwx,g:$REVIEW_WEB_GROUP:rwx,d:u:$deploy_uid:rwx,d:g:$REVIEW_WEB_GROUP:rwx" {} +
    find "$app_dir/storage" "$app_dir/bootstrap/cache" -type f -user "$deploy_uid" \
        -exec setfacl --modify "u:$deploy_uid:rw,g:$REVIEW_WEB_GROUP:rw" {} +
}

checkout_app() {
    if [[ ! -d "$app_dir/.git" ]]; then git init --quiet "$app_dir"; fi
    git -C "$app_dir" config advice.detachedHead false
    git -C "$app_dir" fetch "$REVIEW_REPOSITORY_URL" "$REVIEW_COMMIT_SHA"
    git -C "$app_dir" checkout --force FETCH_HEAD
}

deploy_app() {
    : "${REVIEW_BASE_URL:?}" "${REVIEW_AUTH_API_ENDPOINT:?}" "${REVIEW_REPOSITORY_URL:?}" "${REVIEW_COMMIT_SHA:?}"
    : "${REVIEW_PORT_MIN:=6100}" "${REVIEW_PORT_MAX:=6199}"
    if [[ ! "$REVIEW_PORT_MIN" =~ ^61[0-9]{2}$ || ! "$REVIEW_PORT_MAX" =~ ^61[0-9]{2}$ ]] \
        || (( REVIEW_PORT_MIN > REVIEW_PORT_MAX )); then
        fail "Reverb ports must be within 6100-6199, with min <= max"
    fi
    command -v setfacl > /dev/null || fail "setfacl is required; install the host's acl package"
    local app_url="${REVIEW_BASE_URL%/}$url_path" port

    mkdir -p "$app_dir" "$REVIEW_ROOT/www/review" "$REVIEW_ROOT/ports" "$REVIEW_ROOT/cache/composer" "$REVIEW_ROOT/cache/npm"
    checkout_app

    port="$(allocate_port)"
    echo "review-app: deploying '$REVIEW_SLUG' to $app_url (Reverb port $port)"
    mysql_exec "CREATE DATABASE IF NOT EXISTS \`$db_name\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    write_review_env "$app_dir/.env" "$app_url" "$db_name" "$port"
    fix_permissions
    (
        cd "$app_dir"
        export COMPOSER_CACHE_DIR="$REVIEW_ROOT/cache/composer" npm_config_cache="$REVIEW_ROOT/cache/npm"
        scripts/deploy.sh --no-dev --prune-node-modules "$url_path"
    )
    fix_permissions
    ln --symbolic --force --no-dereference "$app_dir/public" "$web_link"
    systemctl --user enable "${units[@]}"
    systemctl --user restart "${units[@]}"
    echo "review-app: '$REVIEW_SLUG' is available at $app_url"
}

case "$action" in
    deploy) deploy_app ;;
    destroy) destroy_app ;;
esac
