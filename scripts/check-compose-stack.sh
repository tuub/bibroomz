#!/bin/sh

# Builds the images compose.yaml describes, starts the whole stack, and checks
# that the app answers. The Dockerfile and compose.yaml are only ever used by
# hand, so nothing else notices when a base image bump or an edit breaks them.
#
# Writes .env from .env.example, which is why it refuses to run next to an
# existing one.

set -eu

project="${COMPOSE_PROJECT_NAME:-roomz-compose-check}"

compose() {
    docker compose --project-name "$project" "$@"
}

if [ -e .env ]; then
    echo ".env already exists; run this in a clean checkout." >&2
    exit 1
fi

cleanup() {
    status=$?

    if [ "$status" -ne 0 ]; then
        compose logs --no-color || true
    fi

    compose down --volumes --remove-orphans || true
    rm -f .env
    exit "$status"
}
trap cleanup EXIT

# .env.example leaves APP_KEY empty, and the app refuses to encrypt anything
# without one.
app_key="base64:$(head -c 32 /dev/urandom | base64)"
awk -v key="$app_key" '/^APP_KEY=/ { print "APP_KEY=" key; next } { print }' \
    .env.example >.env

compose build
compose up --detach --wait --wait-timeout 300

compose exec -T frankenphp \
    php artisan migrate --force --seed

# The ports are published on the Docker host's loopback only, so ask from
# inside the container. Caddy serves the site under APP_URL.
# shellcheck disable=SC2016 # $APP_URL is the container's, not this shell's.
compose exec -T frankenphp \
    sh -c 'curl --silent --show-error --fail --location --output /dev/null "$APP_URL"'

echo "The compose stack builds, starts and answers."
