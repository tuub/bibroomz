#!/usr/bin/env bash

# Migrates a database up, rolls the whole thing back, and migrates it up again.
#
# Every migration in database/migrations/ defines down(), and until now nothing
# ever ran one: deploy.sh and db-restore.sh only migrate forward, and the test
# suites build their schema from scratch. A down() that drops the wrong table,
# forgets an index or trips over a foreign key is therefore found during the
# rollback someone is attempting because the deploy already went wrong.
#
# The rollback is a real one, against a seeded database: seeded rows carry the
# foreign keys that make a down() fail, and an empty schema does not.
#
# Runs against MariaDB rather than the SQLite the default suite uses, because
# that is what production runs and SQLite quietly accepts schema changes -- a
# dropped column, a changed foreign key -- that MariaDB rejects.

set -o errexit
set -o nounset
set -o pipefail

ROOT_DIR="${ROOT_DIR:-$(pwd)}"

cd "$ROOT_DIR"

# There is no .env in CI, and artisan boots from the environment. The key is
# the one phpunit.xml uses; nothing here encrypts anything worth protecting.
export APP_ENV="${APP_ENV:-testing}"
export APP_DEBUG="${APP_DEBUG:-false}"
export APP_KEY="${APP_KEY:-base64:TF9u2T3Sw37w0oo3Ax8hn7XJWrD8mBcndOwWw7AkGXQ=}"
export TELESCOPE_ENABLED="${TELESCOPE_ENABLED:-false}"

# Seeding writes rows a rollback then has to get past, which is the point, but
# the example institution pulls in a good deal more of them for no extra
# coverage here.
export DB_SEED_EXAMPLE_INSTITUTION="${DB_SEED_EXAMPLE_INSTITUTION:-false}"

# Writes the current table names to $TABLE_LIST_FILE, one per line. tinker
# returns 1 when the code it is given throws, so errexit covers this.
#
# shellcheck disable=SC2016 # The $variables below are PHP's, not this shell's.
tables() {
    php artisan tinker --execute='
        $tables = \Illuminate\Support\Facades\Schema::getTableListing(schemaQualified: false);

        sort($tables);

        file_put_contents(getenv("TABLE_LIST_FILE"), implode(PHP_EOL, $tables));
    '
}

TABLE_LIST_FILE="$(mktemp)"
export TABLE_LIST_FILE
trap 'rm -f "$TABLE_LIST_FILE"' EXIT

echo "Migrating up, with seeders."
php artisan migrate --force --seed

tables
migrated="$(grep --count . "$TABLE_LIST_FILE" || true)"
echo "$migrated tables after migrating."

if [ "$migrated" -lt 2 ]; then
    echo "Migrating created no tables; the database connection is not doing what this expects." >&2
    exit 1
fi

echo "Rolling every migration back."
php artisan migrate:reset --force

# A down() that forgets to drop what its up() created leaves the table behind.
# The next migrate would fail on it, but only for as long as this job runs on a
# database that was migrated once -- on a fresh one it passes, and the rollback
# stays broken. So the leftovers are the assertion.
tables
leftovers="$(grep --invert-match --line-regexp 'migrations' "$TABLE_LIST_FILE" | grep . || true)"

if [ -n "$leftovers" ]; then
    echo "Rolling back left these tables behind:" >&2
    echo "$leftovers" >&2
    echo "The down() of the migration that created each one does not undo its up()." >&2
    exit 1
fi

echo "Rolling back emptied the schema."

# A rollback is only useful if the schema can be built again afterwards, which
# is the state a recovered deploy migrates from.
echo "Migrating up again."
php artisan migrate --force

tables
remigrated="$(grep --count . "$TABLE_LIST_FILE" || true)"

if [ "$remigrated" -ne "$migrated" ]; then
    echo "Migrating after a rollback produced $remigrated tables, not the $migrated it produced before." >&2
    exit 1
fi

echo "Every migration rolls back and runs again."
