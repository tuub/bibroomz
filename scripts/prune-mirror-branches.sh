#!/bin/sh

set -eu

: "${CI_API_V4_URL:?}"
: "${CI_PROJECT_ID:?}"
: "${GITLAB_TOKEN:?}"

github_repo="${GITHUB_REPO:-tuub/bibroomz}"
branch_prefix="${BRANCH_PREFIX:-dependabot/}"

github_branches="$(mktemp)"
gitlab_branches="$(mktemp)"
trap 'rm -f "$github_branches" "$gitlab_branches"' EXIT

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

echo "$stale" | while IFS= read -r branch; do
    [ -z "$branch" ] && continue
    encoded="$(printf '%s' "$branch" | jq -sRr @uri)"
    echo "Deleting stale mirror branch: $branch"
    curl --silent --show-error --fail \
        --request DELETE \
        --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
        "$CI_API_V4_URL/projects/$CI_PROJECT_ID/repository/branches/$encoded"
done
