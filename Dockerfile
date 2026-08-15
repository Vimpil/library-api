FROM php:8.3-cli-alpine

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN apk add --no-cache \
        gnu-libiconv \
        icu-data-en \
        icu-libs \
        libzip \
    && apk add --no-cache --virtual .php-build-deps \
        icu-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" intl zip \
    && apk del .php-build-deps \
    && addgroup -g "${GROUP_ID}" app \
    && adduser -D -u "${USER_ID}" -G app app

ENV LD_PRELOAD=/usr/lib/preloadable_libiconv.so

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/99-library-api.ini

WORKDIR /app

USER app
