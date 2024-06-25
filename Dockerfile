FROM php:8.3-cli as php-build

COPY --from=composer /usr/bin/composer /usr/bin/composer

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

FROM node:20 as node-build

WORKDIR /var/www
COPY package.json package-lock.json ./
RUN npm clean-install --ignore-scripts --no-audit --no-fund

COPY . .

COPY --from=php-build /var/www/vendor /var/www/vendor
COPY --from=php-build /var/www/resources/js/ziggy.js /var/www/resources/js/ziggy.js

RUN mkdir -p /var/www/public/vendor \
    && cp -r /var/www/vendor/laravel/telescope/public /var/www/public/vendor/telescope

ARG CACHEBUST
RUN --mount=type=secret,required=true,id=.env,target=/var/www/.env npm run build

###

FROM php:8.3-fpm as php-fpm

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

FROM caddy:2 as caddy

COPY --from=node-build /var/www/public /var/www/public
