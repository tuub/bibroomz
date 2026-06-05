ARG DOCKERHUB_IMAGE_PREFIX=

FROM ${DOCKERHUB_IMAGE_PREFIX}composer:2.10.0@sha256:1b73755de4f19775ba6087fd5313664493e06fab72b6fc27dc2044e87bb7c4c3 AS composer-bin

FROM ${DOCKERHUB_IMAGE_PREFIX}dunglas/frankenphp:1.12.3-php8.3.31-trixie@sha256:f70becba75acdba38d5d3da7ed07400d2e070e950f988a65623a5d1276265e4a AS frankenphp-base

RUN install-php-extensions \
    bcmath \
    intl \
    opcache \
    pcntl \
    pcov \
    pdo_mysql \
    pdo_sqlite \
    redis \
    sockets \
    sqlite3 \
    zip

###

FROM frankenphp-base AS php-build

COPY --from=composer-bin /usr/bin/composer /usr/bin/composer

RUN apt-get --yes update \
    && apt-get --yes install --no-install-recommends git unzip \
    && rm --recursive --force /var/lib/apt/lists/*

WORKDIR /var/www
COPY composer.json composer.lock ./
RUN composer install --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

ARG CACHEBUST
RUN --mount=type=secret,required=true,id=.env,target=/var/www/.env \
    php artisan ziggy:generate

###

FROM ${DOCKERHUB_IMAGE_PREFIX}node:22.22.3-bookworm@sha256:1031993481795705055273f2eef0c24597abdcb277d6e058c82f78cbbdef92a6 AS node-build

WORKDIR /var/www
COPY package.json package-lock.json ./
RUN npm clean-install --ignore-scripts --no-audit --no-fund

COPY . .

COPY --from=php-build /var/www/vendor /var/www/vendor
COPY --from=php-build /var/www/resources/js/ziggy.js /var/www/resources/js/ziggy.js

ARG CACHEBUST
RUN --mount=type=secret,required=true,id=.env,target=/var/www/.env npm run build

###

FROM frankenphp-base AS frankenphp

WORKDIR /var/www
COPY . .

COPY --from=node-build /var/www/public /var/www/public
COPY --from=php-build /var/www/vendor /var/www/vendor

RUN chown www-data: /var/www/bootstrap/cache \
    && chown -R www-data: /var/www/storage
