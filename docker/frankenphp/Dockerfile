# FrankenPHP application image for the LTI 1.3 DevKit.
# Serves HTTP directly on :8080 (no Nginx, no PHP-FPM, no Unit).
# Also used by the local docker-compose stack (with dev overrides mounted).

ARG INSTALLER_VERSION=2
FROM mlocati/php-extension-installer:${INSTALLER_VERSION} AS ext-installer

# ---------- Stage 1: dependency build (composer + warmup) ----------
FROM php:8.4-cli-bookworm AS build

ARG REDIS_EXT_VERSION=6.2.0

COPY --from=ext-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions intl zip opcache sockets pcntl redis-${REDIS_EXT_VERSION} \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

WORKDIR /app

# leverage docker layer caching for vendor/
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

COPY . .

# authoritative classmap + prod container warmup + bundle assets
# (no network calls; Redis connection is lazy)
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup --no-interaction \
    && APP_ENV=prod APP_DEBUG=0 php bin/console assets:install public --no-interaction --symlink --relative

# ---------- Stage 2: runtime (FrankenPHP = Caddy + PHP-ZTS, worker mode) ----------
FROM dunglas/frankenphp:1.13.2-php8.4-bookworm AS runtime

ARG REDIS_EXT_VERSION=6.2.0

COPY --from=ext-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions intl zip opcache sockets pcntl redis-${REDIS_EXT_VERSION} \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# prod defaults for ANY SAPI (workers, CLi) — .env's dev values are fallback only
ENV APP_ENV=prod \
    APP_DEBUG=0

COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

COPY --chown=www-data:www-data --from=build /app /app

EXPOSE 8080

USER www-data

# inherited CMD: frankenphp run --config /etc/frankenphp/Caddyfile --adapter caddyfile
