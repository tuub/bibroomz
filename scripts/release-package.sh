#!/usr/bin/env bash

# Release a package from this repository: tag the commit GitLab merged
# <directory name>@<version> and push the tag to both remotes, which is what
# the publish jobs in .gitlab-ci.yml and .github/workflows/ wait for.
#
# It tags origin/main rather than HEAD, and reads the version from that same
# commit. A merge request that squashes or rebases gives the commit on main a
# different hash than anything in the local branch, and a tag on the local one
# would pin a release to a commit that never lands on main. The publish would
# still succeed, which is what makes that easy to miss.
#
#     scripts/release-package.sh <package directory>

set -o errexit
set -o nounset
set -o pipefail

branch=main
package_directory=
assume_yes=false
dry_run=false

usage() {
    cat <<'USAGE'
Usage: scripts/release-package.sh [options] <package directory>

  --branch <name>  Branch whose merged commit is tagged. Default: main
  --dry-run        Run every check and print the tag, but do not create or
                   push it.
  --yes            Do not ask for confirmation.
  --help           Show this message.
USAGE
}

while [ "$#" -gt 0 ]; do
    case "$1" in
        --branch)
            shift
            branch="${1:?--branch needs a value}"
            ;;
        --dry-run) dry_run=true ;;
        --yes) assume_yes=true ;;
        --help)
            usage
            exit 0
            ;;
        -*)
            echo "Unknown option: $1" >&2
            usage >&2
            exit 1
            ;;
        *)
            if [ -n "$package_directory" ]; then
                echo "Only one package can be released at a time." >&2
                exit 1
            fi

            package_directory="${1%/}"
            ;;
    esac
    shift
done

if [ -z "$package_directory" ]; then
    usage >&2
    exit 1
fi

cd "$(git rev-parse --show-toplevel)"

for remote in origin github; do
    if ! git remote get-url "$remote" > /dev/null 2>&1; then
        echo "No remote named $remote. Both the GitLab and the GitHub remote have to be configured." >&2
        exit 1
    fi
done

echo "Fetching origin and github." >&2
git fetch --quiet --tags origin "$branch"
git fetch --quiet --tags github

commit=$(git rev-parse "origin/$branch")
manifest="$package_directory/package.json"

if ! merged=$(git show "$commit:$manifest" 2> /dev/null); then
    echo "origin/$branch has no $manifest." >&2
    exit 1
fi

version=$(echo "$merged" | node --print "JSON.parse(require('node:fs').readFileSync(0, 'utf8')).version")
tag="$(basename "$package_directory")@$version"

# The usual mistake: the version was raised but its merge request is still
# open, so main still carries the version before it.
if [ -e "$manifest" ]; then
    local_version=$(node --print "require('./$manifest').version")

    if [ "$local_version" != "$version" ]; then
        echo "Your checkout says $local_version but origin/$branch says $version." >&2
        echo "Merge the version bump before releasing." >&2
        exit 1
    fi
fi

# The fetch above brought both remotes' tags in, so this covers a tag that
# only one of them has.
if git rev-parse --verify --quiet "refs/tags/$tag" > /dev/null; then
    echo "Tag $tag already exists. Neither registry lets a version be replaced, so a" >&2
    echo "bad release needs a new version rather than a retag." >&2
    exit 1
fi

echo >&2
echo "  Package  $(node --print "JSON.parse(require('node:fs').readFileSync(0,'utf8')).name" <<< "$merged")@$version" >&2
echo "  Tag      $tag" >&2
echo "  Commit   $(git log --max-count=1 --format='%h %s' "$commit")" >&2
echo "  Remotes  origin, github" >&2
echo >&2

if [ "$dry_run" = true ]; then
    echo "Dry run; nothing tagged or pushed." >&2
    exit 0
fi

if [ "$assume_yes" = false ]; then
    if [ ! -t 0 ]; then
        echo "Not a terminal. Pass --yes to release without confirmation." >&2
        exit 1
    fi

    printf 'Release this? [y/N] ' >&2
    read -r reply

    case "$reply" in
        [yY] | [yY][eE][sS]) ;;
        *)
            echo "Nothing done." >&2
            exit 1
            ;;
    esac
fi

git tag --annotate --message "$tag" "$tag" "$commit"

# origin first: GitLab is where the pipeline that publishes to its own
# registry runs, and its push mirror may carry the tag to github by itself,
# which makes the second push a no-op rather than a problem.
for remote in origin github; do
    echo "Pushing $tag to $remote." >&2
    git push --quiet "$remote" "refs/tags/$tag"
done

echo >&2
echo "Released $tag. Both pipelines publish to their own registry and the GitHub" >&2
echo "workflow attaches the tarball to a release of the same name; watch them, since" >&2
echo "one can succeed while the other fails." >&2
