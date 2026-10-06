#!/usr/bin/env bash

# Isolated checks for scripts/db-import.sh, the one review job that destroys
# data. The dump and the restore are stubbed; the slug rule, the path it builds
# and the pre-flight read of the target's .env run for real against a host tree.
# bash review/tests/test-db-import.sh
set -euo pipefail

repo_root="$(cd "$(dirname "$0")/../.." && pwd)"
test_root="$(mktemp -d)"
trap 'rm --recursive --force "$test_root"' EXIT
trap 'cat "$test_root/import.log" 2>/dev/null || true' ERR

export REVIEW_TEST_LOG="$test_root/calls.log"
export SOURCE_SSH_USER=dumper
export SOURCE_SSH_HOSTNAME=source.test
export SOURCE_SSH_GIT_DIR=/srv/roomz
export TARGET_SSH_USER=deploy
export TARGET_SSH_HOSTNAME=review.test

mkdir --parents "$test_root/bin"
export PATH="$test_root/bin:$PATH"

# Anything but the dump and the restore runs locally, so the pre-flight reads a
# real file with the real sed. A restore is logged instead of run.
cat > "$test_root/bin/ssh" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
command="${*: -1}"
case "$command" in
    *db-dump.sh*) printf -- '-- dump\n' ;;
    *db-restore.sh*)
        cat > /dev/null
        printf 'restore %s\n' "$command" >> "$REVIEW_TEST_LOG"
        ;;
    *)
        printf 'ssh %s\n' "$command" >> "$REVIEW_TEST_LOG"
        bash -c "$command"
        ;;
esac
SH
chmod +x "$test_root/bin/ssh"

# review/review-app.sh's layout, with DB_DATABASE quoted the way
# review/review.env.template writes it.
app_env() {
    mkdir --parents "$test_root/review/apps/$1"
    printf 'APP_URL="https://review.test/review/%s"\n' "$1" > "$test_root/review/apps/$1/.env"
    if [[ $# -gt 1 ]]; then printf 'DB_DATABASE=%s\n' "$2" >> "$test_root/review/apps/$1/.env"; fi
    printf 'DB_HOST=127.0.0.1\n' >> "$test_root/review/apps/$1/.env"
}
app_env feature-abc-1 '"review_feature_abc_1_ab12cd34"'
# A shared deployment's database, unquoted the way .env.example writes it.
app_env shared-by-mistake roomz
app_env never-deployed

import() {
    : > "$REVIEW_TEST_LOG"
    REVIEW_ROOT="$test_root/review" "$repo_root/scripts/db-import.sh" \
        > "$test_root/import.log" 2>&1
}

refuses() {
    if import; then
        printf 'FAIL: %s\n' "$1" >&2
        cat "$test_root/import.log" >&2
        exit 1
    fi
    [[ "$(grep --count '^restore' "$REVIEW_TEST_LOG" || true)" == 0 ]]
}

export REVIEW_SLUG=feature-abc-1
import
grep --quiet "restore .*cd $test_root/review/apps/feature-abc-1 && scripts/db-restore.sh" "$REVIEW_TEST_LOG"
grep --quiet "into deploy@review.test:$test_root/review/apps/feature-abc-1" "$test_root/import.log"
grep --quiet "database is 'review_feature_abc_1_ab12cd34'" "$test_root/import.log"
printf 'PASS: a slug alone resolves to the app directory and restores there\n'

# scripts/db-restore.sh drops every table of whatever it reaches, so this has to
# stop before the dump.
REVIEW_SLUG=shared-by-mistake refuses 'restored over a non-review database'
grep --quiet "'roomz' is not a review app database" "$test_root/import.log"
printf 'PASS: a target that is not a review database is refused before the dump\n'

REVIEW_SLUG=never-deployed refuses 'restored into an app with no database'
grep --quiet 'no DB_DATABASE' "$test_root/import.log"
printf 'PASS: a checkout with no database configured is refused\n'

REVIEW_SLUG=was-never-here refuses 'restored into an app that does not exist'
printf 'PASS: an app that was never deployed is refused\n'

REVIEW_SLUG='Feature/ABC 1' import
grep --quiet "restore .*cd $test_root/review/apps/feature-abc-1 && scripts/db-restore.sh" "$REVIEW_TEST_LOG"
printf 'PASS: a branch name resolves to the same app as its slug\n'

REVIEW_SLUG='../../srv/roomz' refuses 'followed a slug out of apps/'
grep --quiet "into deploy@review.test:$test_root/review/apps/srv-roomz$" "$test_root/import.log"
printf 'PASS: a slug that would escape apps/ is flattened into one segment\n'

# Nothing survives the rule for these, so they name no app at all.
for slug in '///' '-' '...'; do
    REVIEW_SLUG="$slug" refuses "accepted the slug '$slug'"
    grep --quiet "'$slug' is no review app's branch or slug" "$test_root/import.log"
done
printf 'PASS: an input that slugifies to nothing is refused\n'

# There is no /srv/review here, so the pre-flight refuses right after printing
# the path, which is all this case has to see.
unset REVIEW_ROOT
REVIEW_SLUG=feature-abc-1 "$repo_root/scripts/db-import.sh" > "$test_root/import.log" 2>&1 || true
grep --quiet 'into deploy@review.test:/srv/review/apps/feature-abc-1' "$test_root/import.log"
printf 'PASS: an unset REVIEW_ROOT leaves the host tree at its default\n'

# Without a slug the pre-flight is skipped and the target comes from the
# environment: the path the scheduled import-database job takes.
unset REVIEW_SLUG
: > "$REVIEW_TEST_LOG"
TARGET_SSH_GIT_DIR=/srv/roomz "$repo_root/scripts/db-import.sh" > "$test_root/import.log" 2>&1
grep --quiet 'restore .*cd /srv/roomz && scripts/db-restore.sh' "$REVIEW_TEST_LOG"
[[ "$(grep --count 'DB_DATABASE' "$REVIEW_TEST_LOG" || true)" == 0 ]]
printf 'PASS: without a slug the explicit target is used and nothing is pre-checked\n'

refuses 'ran without any target'
printf 'PASS: neither a slug nor a target directory imports nothing\n'
