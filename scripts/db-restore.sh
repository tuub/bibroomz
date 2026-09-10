#!/bin/bash
# Runs on the target host over SSH. Reads a mariadb-dump SQL stream from stdin and restores it
# over the app database, taking the app down for the duration so nothing reads a half-restored DB.
# The dump reflects the source host's schema, which may be behind the code already deployed here,
# so migrations run afterwards to bring the restored DB up to what that code expects.

set -e
set -o pipefail

set -a
source .env
set +a

php artisan down --retry=15
trap 'php artisan up' EXIT

export MYSQL_PWD="$DB_PASSWORD"

mariadb --host="$DB_HOST" --user="$DB_USERNAME" "$DB_DATABASE" < scripts/drop-all-tables.sql

mariadb \
    --host="$DB_HOST" \
    --user="$DB_USERNAME" \
    "$DB_DATABASE"

php artisan migrate --force
