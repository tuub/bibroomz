FROM composer:2.10.0@sha256:1b73755de4f19775ba6087fd5313664493e06fab72b6fc27dc2044e87bb7c4c3 AS composer-bin

FROM php:8.3.31-cli-trixie@sha256:861deb86c83e92f416ebdb1a1d15c957474d2f39e112c7edea4446070afd8055 AS php-build

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer

RUN apt-get -y update
RUN apt-get -y install --no-install-recommends git libzip-dev unzip
RUN pecl install redis && docker-php-ext-enable redis
RUN docker-php-ext-install bcmath zip

WORKDIR /var/www
COPY composer.json composer.lock ./
RUN composer install --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

ARG CACHEBUST
RUN --mount=type=secret,required=true,id=.env,target=/var/www/.env php artisan ziggy:generate

###

FROM node:20.20.2-bookworm@sha256:8f693eaa7e0a8e71560c9a82b55fd54c2ae920a2ba5d2cde28bac7d1c01c9ba5 AS node-build

WORKDIR /var/www
COPY package.json package-lock.json ./
RUN npm clean-install --ignore-scripts --no-audit --no-fund

COPY . .

COPY --from=php-build /var/www/vendor /var/www/vendor
COPY --from=php-build /var/www/resources/js/ziggy.js /var/www/resources/js/ziggy.js

ARG CACHEBUST
RUN --mount=type=secret,required=true,id=.env,target=/var/www/.env npm run build

###

FROM php:8.3.31-fpm-trixie@sha256:b1a1333bc68ab2b55f6422e31a34d3feefa0865f486fc14004b22f87236aa2d3 AS php-fpm

RUN apt-get -y update
RUN apt-get -y install --no-install-recommends libicu-dev
RUN pecl install redis && docker-php-ext-enable redis
RUN docker-php-ext-install intl pcntl pdo_mysql

WORKDIR /var/www
COPY . .

COPY --from=node-build /var/www/public /var/www/public
COPY --from=php-build /var/www/vendor /var/www/vendor

RUN chown www-data: /var/www/bootstrap/cache \
    && chown -R www-data: /var/www/storage

###

FROM caddy:2.11.3@sha256:ec18ee54aab3315c22e25f3b2babda73ff8007d39b13b3bd1bfffa2f0444c7d9 AS caddy

COPY --from=node-build /var/www/public /var/www/public
