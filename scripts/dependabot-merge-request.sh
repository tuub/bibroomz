#!/bin/sh

set -eu

: "${CI_API_V4_URL:?}"
: "${CI_COMMIT_BRANCH:?}"
: "${CI_DEFAULT_BRANCH:?}"
: "${CI_PROJECT_ID:?}"
: "${GITLAB_TOKEN:?}"

target_branch="${TARGET_BRANCH:-$CI_DEFAULT_BRANCH}"

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
        "$CI_API_V4_URL/projects/$CI_PROJECT_ID/merge_requests"
}

existing="$(fetch_merge_requests opened)"

iid="$(echo "$existing" | jq --raw-output '.[0].iid // empty')"

if [ -z "$iid" ]; then
    previous="$(fetch_merge_requests all)"
    previous_iid="$(echo "$previous" | jq --raw-output 'map(select(.state != "opened"))[0].iid // empty')"
    previous_state="$(echo "$previous" | jq --raw-output 'map(select(.state != "opened"))[0].state // empty')"

    if [ -n "$previous_iid" ]; then
        echo "Merge request !$previous_iid for $CI_COMMIT_BRANCH is $previous_state; not creating another."
        exit 0
    fi

    created="$(curl --silent --show-error --fail \
        --request POST \
        --header "PRIVATE-TOKEN: $GITLAB_TOKEN" \
        --data-urlencode "source_branch=$CI_COMMIT_BRANCH" \
        --data-urlencode "target_branch=$target_branch" \
        --data-urlencode "title=chore(deps): $CI_COMMIT_BRANCH" \
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
