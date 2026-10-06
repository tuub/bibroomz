#!/bin/bash
# Runs on the CI runner. Streams a DB dump from the source host straight into a restore on the
# target host over SSH, without touching this machine's disk.
#
# REVIEW_SLUG picks a review app on the target host instead of that host's own deployment, as either
# the branch name or the slug it deploys under. The path is derived here rather than passed in, and
# the pre-flight check below refuses a target that is no review app: scripts/db-restore.sh drops
# every table before it restores.

set -e
set -o pipefail

fail() { echo "db-import: $*" >&2; exit 1; }

: "${SOURCE_SSH_USER:?}" "${SOURCE_SSH_HOSTNAME:?}" "${SOURCE_SSH_GIT_DIR:?}"
: "${TARGET_SSH_USER:?}" "${TARGET_SSH_HOSTNAME:?}"

# CI_COMMIT_REF_SLUG's rule, the one review/review-app.sh deployed the app under: lower cased,
# everything but 0-9 and a-z to a dash, cut to 63 characters, no leading or trailing dashes. A slug
# survives it unchanged; nothing that could reach out of apps/ survives it at all.
slugify() {
    local slug="${1,,}"
    slug="${slug//[^a-z0-9]/-}"
    slug="${slug:0:63}"
    while [[ "$slug" == -* ]]; do slug="${slug#-}"; done
    while [[ "$slug" == *- ]]; do slug="${slug%-}"; done
    printf '%s' "$slug"
}

if [[ -n "${REVIEW_SLUG:-}" ]]; then
    slug="$(slugify "$REVIEW_SLUG")"
    [[ "$slug" =~ ^[a-z0-9][a-z0-9-]{0,62}$ ]] || fail "'$REVIEW_SLUG' is no review app's branch or slug"
    TARGET_SSH_GIT_DIR="${REVIEW_ROOT:-/srv/review}/apps/$slug"
fi
: "${TARGET_SSH_GIT_DIR:?}"

echo "db-import: from ${SOURCE_SSH_USER}@${SOURCE_SSH_HOSTNAME}:${SOURCE_SSH_GIT_DIR}"
echo "db-import: into ${TARGET_SSH_USER}@${TARGET_SSH_HOSTNAME}:${TARGET_SSH_GIT_DIR}"

# review/review-app.sh names every review database review_<slug>_<hash>, so the target's own .env is
# what says whether this path is a review app. Read, never sourced: nothing here evaluates it.
if [[ -n "${REVIEW_SLUG:-}" ]]; then
    # shellcheck disable=SC2029 # $TARGET_SSH_GIT_DIR must expand locally, not remotely
    database="$(ssh "${TARGET_SSH_USER}@${TARGET_SSH_HOSTNAME}" \
        "sed --quiet 's/^DB_DATABASE=\"\?\([^\"]*\)\"\?\$/\1/p' $TARGET_SSH_GIT_DIR/.env")"
    [[ -n "$database" ]] \
        || fail "no DB_DATABASE in $TARGET_SSH_GIT_DIR/.env; is '$slug' deployed, and REVIEW_ROOT this host's tree?"
    [[ "$database" == review_* ]] || fail "'$database' is not a review app database; refusing to restore over it"
    echo "db-import: target database is '$database'"
fi

# shellcheck disable=SC2029 # $SOURCE_SSH_GIT_DIR/$TARGET_SSH_GIT_DIR must expand locally, not remotely
ssh "${SOURCE_SSH_USER}@${SOURCE_SSH_HOSTNAME}" "cd $SOURCE_SSH_GIT_DIR && scripts/db-dump.sh" \
    | ssh "${TARGET_SSH_USER}@${TARGET_SSH_HOSTNAME}" "cd $TARGET_SSH_GIT_DIR && scripts/db-restore.sh"
