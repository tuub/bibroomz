#!/bin/sh

set -eu

: "${CI_API_V4_URL:?}"
: "${CI_PROJECT_ID:?}"
: "${GITLAB_TOKEN:?}"

github_repo="${GITHUB_REPO:-tuub/bibroomz}"
branch_prefix="${BRANCH_PREFIX:-dependabot/}"

github_branches="$(mktemp)"
gitlab_branches="$(mktemp)"
stale_branches="$(mktemp)"
trap 'rm -f "$github_branches" "$gitlab_branches" "$stale_branches"' EXIT

: >"$github_branches"
page=1
while :; do
    response="$(curl --silent --show-error --fail \
        --get \
        --data-urlencode "per_page=100" \
        --data-urlencode "page=$page" \
        "https://api.github.com/repos/$github_repo/branches")"

    count="$(echo "$response" | jq 'length')"
    echo "$response" | jq --raw-output '.[].name' >>"$github_branches"

    [ "$count" -lt 100 ] && break
    page=$((page + 1))
done

if [ ! -s "$github_branches" ]; then
    echo "GitHub returned no branches for $github_repo; refusing to prune (would delete everything)." >&2
    exit 1
fi

: >"$gitlab_branches"
page=1
while :; do
    response="$(curl --silent --show-error --fail \
        --get \
        --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
        --data-urlencode "per_page=100" \
        --data-urlencode "page=$page" \
        "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/branches")"

    count="$(echo "$response" | jq 'length')"
    echo "$response" | jq --raw-output --arg prefix "$branch_prefix" \
        '.[] | select(.name | startswith($prefix)) | .name' >>"$gitlab_branches"

    [ "$count" -lt 100 ] && break
    page=$((page + 1))
done

stale="$(grep -vxFf "$github_branches" "$gitlab_branches" || true)"

if [ -z "$stale" ]; then
    echo "No stale mirror branches to prune."
    exit 0
fi

# Read from a file rather than a pipe: the loop keeps track of failures, and a
# pipeline would run it in a subshell where that tally is lost.
printf '%s\n' "$stale" >"$stale_branches"

# The branch can disappear between the listing and the delete: this job runs
# right after dependabot-merge-request.sh has enabled auto-merge with
# should_remove_source_branch, so GitLab deletes the source branch itself the
# moment the pipeline goes green. A 404 means the pruning is already done, not
# that something went wrong, so it must not fail the job.
exit_status=0

while IFS= read -r branch; do
    [ -z "$branch" ] && continue
    encoded="$(printf '%s' "$branch" | jq -sRr @uri)"
    echo "Deleting stale mirror branch: $branch"

    http_status="$(curl --silent --show-error \
        --output /dev/null \
        --write-out "%{http_code}" \
        --request DELETE \
        --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
        "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/branches/$encoded")" || http_status="000"

    case "$http_status" in
        2??)
            ;;
        404)
            echo "Branch $branch is already gone; nothing to delete."
            ;;
        *)
            echo "Failed to delete $branch (HTTP $http_status)." >&2
            exit_status=1
            ;;
    esac
done <"$stale_branches"

exit "$exit_status"
