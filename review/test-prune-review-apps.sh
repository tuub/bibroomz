#!/usr/bin/env bash

# Isolated checks for scripts/prune-review-apps.sh. The GitLab API and the
# review host are stubbed; jq, grep and the script's own logic are real.
# nix shell --inputs-from . nixpkgs#jq --command bash review/test-prune-review-apps.sh
set -euo pipefail

repo_root="$(cd "$(dirname "$0")/.." && pwd)"
test_root="$(mktemp -d)"
trap 'rm --recursive --force "$test_root"' EXIT
trap 'cat "$test_root/prune.log" 2>/dev/null || true' ERR

export REVIEW_TEST_LOG="$test_root/calls.log"
export CI_API_V4_URL="https://gitlab.test/api/v4"
export CI_PROJECT_ID=42
export GITLAB_TOKEN=token
export SSH_USER=deploy
export SSH_HOSTNAME=review.test
export REVIEW_ROOT="/srv/review"

mkdir --parents "$test_root/bin"
export PATH="$test_root/bin:$PATH"

# A branch whose slug is not its name, so the slug rule is exercised too:
# "Feature/ABC 1" deploys to the host directory "feature-abc-1".
export REVIEW_TEST_BRANCHES='[{"name":"main"},{"name":"Feature/ABC 1"}]'
export REVIEW_TEST_ENVIRONMENTS='[
    {"id":11,"name":"review/main"},
    {"id":12,"name":"review/gone"},
    {"id":13,"name":"review/feature-abc-1"}
]'

cat > "$test_root/bin/curl" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
url="${*: -1}"
case "$url" in
    */repository/branches) printf '%s' "$REVIEW_TEST_BRANCHES" ;;
    */environments) printf '%s' "$REVIEW_TEST_ENVIRONMENTS" ;;
    *)
        printf 'curl %s\n' "$url" >> "$REVIEW_TEST_LOG"
        printf '{}'
        ;;
esac
SH

# The host listing mirrors the environments plus one app GitLab no longer knows
# about at all, which only the host side of the reconciliation can find.
cat > "$test_root/bin/ssh" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
command="${*: -1}"
if [[ "$command" == find* ]]; then
    printf 'main\ngone\nfeature-abc-1\nforgotten\n'
    exit 0
fi
# Consume the script on stdin the way the host's bash would.
cat > /dev/null
printf 'ssh %s\n' "$command" >> "$REVIEW_TEST_LOG"
SH

chmod +x "$test_root/bin/curl" "$test_root/bin/ssh"

: > "$REVIEW_TEST_LOG"
"$repo_root/scripts/prune-review-apps.sh" > "$test_root/prune.log" 2>&1

destroyed="$(grep --count "^ssh .*bash -s destroy" "$REVIEW_TEST_LOG" || true)"
[[ "$destroyed" == 2 ]]
grep --quiet "REVIEW_SLUG='gone'" "$REVIEW_TEST_LOG"
grep --quiet "REVIEW_SLUG='forgotten'" "$REVIEW_TEST_LOG"
grep --quiet "REVIEW_ROOT='/srv/review'" "$REVIEW_TEST_LOG"
printf 'PASS: only apps without a branch are destroyed, including one GitLab forgot\n'

# "main" and the slug of "Feature/ABC 1" both still have a branch.
[[ "$(grep --count "REVIEW_SLUG='main'" "$REVIEW_TEST_LOG" || true)" == 0 ]]
[[ "$(grep --count "REVIEW_SLUG='feature-abc-1'" "$REVIEW_TEST_LOG" || true)" == 0 ]]
[[ "$(grep --count "environments/11/stop" "$REVIEW_TEST_LOG" || true)" == 0 ]]
[[ "$(grep --count "environments/13/stop" "$REVIEW_TEST_LOG" || true)" == 0 ]]
printf 'PASS: a live branch keeps its app and environment, slug rule included\n'

grep --quiet "environments/12/stop?force=true" "$REVIEW_TEST_LOG"
grep --quiet "environments/review_apps?before=.*dry_run=false" "$REVIEW_TEST_LOG"
printf 'PASS: orphaned environments are stopped and the stopped ones scheduled for deletion\n'

# An empty branch list is indistinguishable from "every branch was deleted".
: > "$REVIEW_TEST_LOG"
if REVIEW_TEST_BRANCHES='[]' "$repo_root/scripts/prune-review-apps.sh" > "$test_root/prune.log" 2>&1; then
    printf 'FAIL: pruned with an empty branch list\n' >&2
    exit 1
fi
[[ ! -s "$REVIEW_TEST_LOG" ]]
printf 'PASS: an empty branch list destroys nothing\n'
