#!/bin/sh

prefix="$1"

composer install
npm clean-install

php artisan optimize --except=routes
php artisan ziggy:generate

php artisan migrate --force --seed

npm run build -- --base="$prefix/build"
