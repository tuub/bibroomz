#!/bin/sh

set -eu

: "${CI_API_V4_URL:?}"
: "${CI_PROJECT_ID:?}"
: "${GITLAB_TOKEN:?}"
: "${GITHUB_TOKEN:?}"

github_repo="${GITHUB_REPO:-tuub/bibroomz}"
github_owner="${github_repo%%/*}"
github_api="${GITHUB_API_BASE:-https://api.github.com}"
branch_prefix="${BRANCH_PREFIX:-dependabot/}"

fetch_closed_mrs() {
    state="$1"
    page=1
    while :; do
        response="$(curl --silent --show-error --fail \
            --get \
            --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
            --data-urlencode "state=$state" \
            --data-urlencode "order_by=updated_at" \
            --data-urlencode "sort=desc" \
            --data-urlencode "per_page=100" \
            --data-urlencode "page=$page" \
            "$CI_API_V4_URL/projects/$CI_PROJECT_ID/merge_requests")"

        count="$(echo "$response" | jq 'length')"
        [ "$count" -eq 0 ] && break

        echo "$response" | jq --raw-output --arg prefix "$branch_prefix" \
            '.[] | select(.source_branch | startswith($prefix)) | "\(.source_branch)\t\(.iid)\t\(.web_url)\t\(.state)"'

        [ "$count" -lt 100 ] && break
        page=$((page + 1))
    done
}

tab="$(printf '\t')"

{
    fetch_closed_mrs merged
    fetch_closed_mrs closed
} | while IFS="$tab" read -r branch iid web_url state; do
    [ -z "$branch" ] && continue

    open_prs="$(curl --silent --show-error --fail \
        --get \
        --header "Authorization: Bearer $GITHUB_TOKEN" \
        --header "Accept: application/vnd.github+json" \
        --data-urlencode "head=$github_owner:$branch" \
        --data-urlencode "state=open" \
        "$github_api/repos/$github_repo/pulls")"

    pr_number="$(echo "$open_prs" | jq --raw-output '.[0].number // empty')"
    [ -z "$pr_number" ] && continue

    echo "Closing GitHub PR #$pr_number for $branch (GitLab MR !$iid is $state)"

    comment_body="$(jq --null-input --arg state "$state" --arg url "$web_url" \
        '{body: ("Closed: " + $state + " via GitLab mirror MR " + $url + ".")}')"

    curl --silent --show-error --fail \
        --request POST \
        --header "Authorization: Bearer $GITHUB_TOKEN" \
        --header "Accept: application/vnd.github+json" \
        --header "Content-Type: application/json" \
        --data "$comment_body" \
        "$github_api/repos/$github_repo/issues/$pr_number/comments" >/dev/null

    curl --silent --show-error --fail \
        --request PATCH \
        --header "Authorization: Bearer $GITHUB_TOKEN" \
        --header "Accept: application/vnd.github+json" \
        --header "Content-Type: application/json" \
        --data '{"state":"closed"}' \
        "$github_api/repos/$github_repo/pulls/$pr_number" >/dev/null
done
