#!/bin/sh

prefix="$1"

composer install
npm clean-install

php artisan config:cache
php artisan route:cache

php artisan migrate --force --seed

php artisan ziggy:generate

npm run build -- --base="$prefix/build"
