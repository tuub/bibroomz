FROM ubuntu:22.04

ARG NODE_VERSION=18

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update && apt-get install -y \
        ca-certificates \
        curl \
        php8.1 \
        php8.1-fpm \
        php8.1-dom \
        php8.1-mbstring \
        php8.1-mysql \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -sLS https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm
