#!/bin/sh
# Destroys review apps whose branch no longer exists and clears their
# environments out of GitLab. stop-review covers the ordinary case; this is the
# backstop for the branches it never ran for.

set -eu

: "${CI_API_V4_URL:?}"
: "${CI_PROJECT_ID:?}"
: "${GITLAB_TOKEN:?}"
: "${SSH_USER:?}"
: "${SSH_HOSTNAME:?}"

review_root="${REVIEW_ROOT:-/srv/review}"
repo_root="$(CDPATH='' cd -- "$(dirname -- "$0")/.." && pwd)"

gitlab_api() {
    curl --silent --show-error --fail \
        --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
        "$@"
}

live_slugs="$(mktemp)"
host_apps="$(mktemp)"
environments="$(mktemp)"
trap 'rm -f "$live_slugs" "$host_apps" "$environments"' EXIT

# deploy-review names the host directory and the environment after
# CI_COMMIT_REF_SLUG: the branch lower cased, with everything but 0-9 and a-z
# replaced by a dash, cut to 63 characters, without leading or trailing dashes.
: >"$live_slugs"
page=1
while :; do
    response="$(gitlab_api --get \
        --data-urlencode "per_page=100" \
        --data-urlencode "page=$page" \
        "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/branches")"

    count="$(echo "$response" | jq 'length')"
    echo "$response" | jq --raw-output \
        '.[].name | ascii_downcase | gsub("[^a-z0-9]"; "-") | .[0:63] | sub("^-+"; "") | sub("-+$"; "")' \
        >>"$live_slugs"

    [ "$count" -lt 100 ] && break
    page=$((page + 1))
done

if [ ! -s "$live_slugs" ]; then
    echo "GitLab returned no branches; refusing to prune (would destroy every review app)." >&2
    exit 1
fi

# The host is the only place a leaked app costs anything, so it is pruned first
# and independently of what GitLab still lists as an environment.
# shellcheck disable=SC2029 # the path is meant to expand here, not on the host.
ssh "${SSH_USER}@${SSH_HOSTNAME}" \
    "find '$review_root/apps' -mindepth 1 -maxdepth 1 -type d -printf '%f\n' 2> /dev/null || true" \
    >"$host_apps"

while IFS= read -r slug; do
    [ -n "$slug" ] || continue
    if grep -qxF "$slug" "$live_slugs"; then
        continue
    fi

    echo "Destroying review app without a branch: $slug"
    # Reading the script from stdin also keeps ssh off this loop's input.
    # shellcheck disable=SC2029 # both values are meant to expand here.
    ssh "${SSH_USER}@${SSH_HOSTNAME}" \
        "REVIEW_ROOT='$review_root' \
         REVIEW_SLUG='$slug' \
         bash -s destroy" < "$repo_root/review/review-app.sh"
done < "$host_apps"

: >"$environments"
page=1
while :; do
    response="$(gitlab_api --get \
        --data-urlencode "per_page=100" \
        --data-urlencode "page=$page" \
        --data-urlencode "states=available" \
        --data-urlencode "search=review/" \
        "$CI_API_V4_URL/projects/$CI_PROJECT_ID/environments")"

    count="$(echo "$response" | jq 'length')"
    echo "$response" | jq --raw-output \
        '.[] | select(.name | startswith("review/")) | "\(.id) \(.name | ltrimstr("review/"))"' \
        >>"$environments"

    [ "$count" -lt 100 ] && break
    page=$((page + 1))
done

while read -r id slug; do
    [ -n "$id" ] || continue
    if grep -qxF "$slug" "$live_slugs"; then
        continue
    fi

    # force skips the on_stop job: the host side is already gone, and the
    # pipeline that would run it may be too.
    echo "Stopping environment review/$slug"
    gitlab_api --request POST \
        "$CI_API_V4_URL/projects/$CI_PROJECT_ID/environments/$id/stop?force=true" > /dev/null
done < "$environments"

# Stopped environments stay in the list until something deletes them, and
# GitLab has no setting for it. The deletion is scheduled rather than immediate,
# and is carried out a week later.
echo "Scheduling deletion of stopped review environments."
now="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
gitlab_api --request DELETE \
    "$CI_API_V4_URL/projects/$CI_PROJECT_ID/environments/review_apps?before=$now&limit=100&dry_run=false"
