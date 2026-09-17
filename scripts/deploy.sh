#!/bin/bash

set -e
set -x

# Optional production-only dependencies and post-build cleanup save disk at
# the cost of slower redeployments. By default, keep the full dependency trees.
composer_flags=()
prune_node_modules=false
prefix=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --no-dev)
            composer_flags+=(--no-dev)
            ;;
        --prune-node-modules)
            prune_node_modules=true
            ;;
        *)
            prefix="$1"
            ;;
    esac
    shift
done

composer install "${composer_flags[@]}"
npm clean-install

php artisan optimize --except=routes

php artisan migrate --force --seed

npm run build -- --base="$prefix/build"

if [[ "$prune_node_modules" == true ]]; then
    # Nothing serves from node_modules at runtime: the app builds no SSR bundle,
    # so public/build holds everything the browser receives.
    rm --recursive --force node_modules
fi
