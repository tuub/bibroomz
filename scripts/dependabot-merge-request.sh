#!/bin/sh

set -eu

: "${CI_API_V4_URL:?}"
: "${CI_COMMIT_BRANCH:?}"
: "${CI_DEFAULT_BRANCH:?}"
: "${CI_PROJECT_ID:?}"
: "${GITLAB_TOKEN:?}"
: "${GITHUB_TOKEN:?}"

target_branch="${TARGET_BRANCH:-$CI_DEFAULT_BRANCH}"
github_repo="${GITHUB_REPO:-tuub/bibroomz}"
github_owner="${github_repo%%/*}"
github_api="${GITHUB_API_BASE:-https://api.github.com}"

# Merge requests are tied to the GitHub pull request they mirror, not to the
# branch or commit: update-flake-lock reuses one branch name for every update,
# and Dependabot rebases change the commit without making it a new update.
# stale-pull-requests.sh reads the same line back.
#
# Sets pr_number rather than printing it: BusyBox sh does not apply set -e
# inside a command substitution, so a failing curl would read as "no pull
# request yet".
fetch_pull_request_number() {
    open_prs="$(curl --silent --show-error --fail \
        --get \
        --header "Authorization: Bearer $GITHUB_TOKEN" \
        --header "Accept: application/vnd.github+json" \
        --data-urlencode "head=$github_owner:$CI_COMMIT_BRANCH" \
        --data-urlencode "state=open" \
        "$github_api/repos/$github_repo/pulls")"

    pr_number="$(echo "$open_prs" | jq --raw-output '.[0].number // empty')"
}

# The mirror can pull the branch before the pull request for it is opened.
pull_request_attempts=6

while :; do
    fetch_pull_request_number
    pull_request_attempts=$((pull_request_attempts - 1))

    if [ -n "$pr_number" ] || [ "$pull_request_attempts" -eq 0 ]; then
        break
    fi

    sleep 10
done

if [ -z "$pr_number" ]; then
    echo "No open GitHub pull request for $CI_COMMIT_BRANCH in $github_repo." >&2
    exit 1
fi

marker="GitHub-PR: #$pr_number"

fetch_merge_requests() {
    state="$1"

    curl --silent --show-error --fail \
        --get \
        --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
        --data-urlencode "state=$state" \
        --data-urlencode "source_branch=$CI_COMMIT_BRANCH" \
        --data-urlencode "target_branch=$target_branch" \
        --data-urlencode "order_by=updated_at" \
        --data-urlencode "sort=desc" \
        --data-urlencode "per_page=100" \
        "$CI_API_V4_URL/projects/$CI_PROJECT_ID/merge_requests"
}

existing="$(fetch_merge_requests opened)"

iid="$(echo "$existing" | jq --raw-output '.[0].iid // empty')"

if [ -n "$iid" ]; then
    # A pull request closed on GitHub and reopened by the next update leaves
    # the merge request pointing at the old one.
    has_marker="$(echo "$existing" | jq --raw-output --arg marker "$marker" \
        '.[0].description // "" | split("\n") | any(. == $marker)')"

    if [ "$has_marker" != "true" ]; then
        curl --silent --show-error --fail \
            --request PUT \
            --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
            --data-urlencode "description=$marker" \
            "$CI_API_V4_URL/projects/$CI_PROJECT_ID/merge_requests/$iid" >/dev/null
    fi
else
    all="$(fetch_merge_requests all)"
    previous="$(echo "$all" | jq --arg marker "$marker" \
        'map(select(.state != "opened" and (.description // "" | split("\n") | any(. == $marker))))[0] // empty')"

    if [ -n "$previous" ]; then
        previous_iid="$(echo "$previous" | jq --raw-output '.iid')"
        previous_state="$(echo "$previous" | jq --raw-output '.state')"
        echo "Merge request !$previous_iid for $CI_COMMIT_BRANCH ($marker) is $previous_state; not creating another."
        exit 0
    fi

    created="$(curl --silent --show-error --fail \
        --request POST \
        --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
        --data-urlencode "source_branch=$CI_COMMIT_BRANCH" \
        --data-urlencode "target_branch=$target_branch" \
        --data-urlencode "title=chore(deps): $CI_COMMIT_BRANCH" \
        --data-urlencode "description=$marker" \
        --data-urlencode "remove_source_branch=true" \
        "$CI_API_V4_URL/projects/$CI_PROJECT_ID/merge_requests")"

    iid="$(echo "$created" | jq --raw-output '.iid')"
fi

if [ -z "$iid" ]; then
    echo "Could not resolve merge request IID for $CI_COMMIT_BRANCH." >&2
    exit 1
fi

merge_status_attempts=12

while [ "$merge_status_attempts" -gt 0 ]; do
    merge_request="$(curl --silent --show-error --fail \
        --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
        "$CI_API_V4_URL/projects/$CI_PROJECT_ID/merge_requests/$iid")"

    detailed_merge_status="$(echo "$merge_request" | jq --raw-output '.detailed_merge_status // "unknown"')"

    case "$detailed_merge_status" in
        approvals_syncing | checking | preparing | unchecked | unknown)
            merge_status_attempts=$((merge_status_attempts - 1))

            if [ "$merge_status_attempts" -gt 0 ]; then
                sleep 5
            fi
            ;;
        *)
            break
            ;;
    esac
done

merge_response="$(mktemp)"
trap 'rm -f "$merge_response"' EXIT

http_status="$(curl --silent --show-error \
    --output "$merge_response" \
    --write-out "%{http_code}" \
    --request PUT \
    --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
    --data-urlencode "auto_merge=true" \
    --data-urlencode "should_remove_source_branch=true" \
    "$CI_API_V4_URL/projects/$CI_PROJECT_ID/merge_requests/$iid/merge")"

case "$http_status" in
    2??)
        echo "Enabled auto-merge for merge request !$iid."
        ;;
    405 | 422)
        echo "Merge request !$iid is not mergeable yet ($detailed_merge_status, HTTP $http_status); leaving it open."
        cat "$merge_response"
        ;;
    *)
        echo "Failed to enable auto-merge for merge request !$iid (HTTP $http_status)." >&2
        cat "$merge_response" >&2
        exit 1
        ;;
esac
