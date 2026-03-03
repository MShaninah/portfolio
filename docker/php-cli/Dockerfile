FROM php:8.4-cli-alpine

RUN apk add --no-cache bash git unzip icu-dev libzip-dev oniguruma-dev \
  && docker-php-ext-configure intl \
  && docker-php-ext-install -j$(nproc) intl zip pdo pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /project
