#!/bin/bash
# Runs on the source host over SSH. Streams a consistent dump of the app database to stdout, using
# the dedicated read-only MariaDB user provisioned by scripts/setup-db-dump-user.sh - not the app's
# own full-privilege .env credentials.

set -e
set -o pipefail

set -a
source ~/.env.db-dump
set +a

export MYSQL_PWD="$DB_PASSWORD"

exec mariadb-dump \
    --single-transaction \
    --skip-add-locks \
    --quick \
    --host="$DB_HOST" \
    --user="$DB_USERNAME" \
    "$DB_DATABASE"
