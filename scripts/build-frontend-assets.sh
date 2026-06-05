#!/usr/bin/env bash

set -o errexit
set -o nounset
set -o pipefail

ROOT_DIR="${ROOT_DIR:-$(pwd)}"
APP_BUILD_KEY="${APP_BUILD_KEY:-base64:TF9u2T3Sw37w0oo3Ax8hn7XJWrD8mBcndOwWw7AkGXQ=}"

cd "$ROOT_DIR"

export APP_ENV="${APP_ENV:-testing}"
export APP_DEBUG="${APP_DEBUG:-false}"
export APP_KEY="${APP_KEY:-$APP_BUILD_KEY}"
export APP_URL="${APP_URL:-http://127.0.0.1:8000}"
export TELESCOPE_ENABLED="${TELESCOPE_ENABLED:-false}"
export VITE_REVERB_PORT="${VITE_REVERB_PORT:-6001}"

php artisan ziggy:generate
npm run build
