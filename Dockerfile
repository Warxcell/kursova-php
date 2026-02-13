# syntax=docker/dockerfile:1.4

ARG PHP_VERSION=8.5
ARG COMPOSER_VERSION=2

FROM composer:${COMPOSER_VERSION} AS composer

FROM php:${PHP_VERSION}-cli-alpine3.23 AS php_base

WORKDIR /app

RUN apk add --no-cache \
                imagemagick \
                imagemagick-jpeg \
                imagemagick-webp \
    && apk add --no-cache --virtual .build-deps \
        g++ \
        make \
        autoconf \
        imagemagick-dev \
        linux-headers \
    && docker-php-ext-install pcntl \
    && pecl install imagick && docker-php-ext-enable imagick \
    && apk del --purge -f .build-deps

COPY --link ./php.ini $PHP_INI_DIR/conf.d/php.override.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"


EXPOSE 8080

CMD ["php", "server.php"]

FROM php_base AS php_builder

COPY --link --from=composer /usr/bin/composer /usr/bin/composer

COPY --link ./composer.json ./composer.lock ./

RUN composer install \
    --no-ansi \
    --no-dev \
    --no-autoloader \
    --no-interaction \
    --no-scripts \
    --no-cache \
    --prefer-dist

COPY --link ./public ./public
COPY --link ./src ./src
COPY --link ./templates ./templates
COPY --link ./server.php ./

RUN composer dump-autoload \
        --optimize \
        --no-dev \
        --classmap-authoritative

FROM php_base AS app_prod

COPY --link --from=php_builder ./app ./
