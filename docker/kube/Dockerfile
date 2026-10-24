# Production image for Cloud Build / Kubernetes: FrankenPHP (NGINX Unit replacement).
# Same content as docker/frankenphp/Dockerfile, duplicated self-contained so that
# `gcloud builds submit` with the repo-root context stays reproducible on its own.
# NOTE: the former private registry base images
# (europe-west1-docker.pkg.dev/tao-artefacts/base-images/*) have been replaced with
# public, pinned base images so the build no longer depends on registry pulls
# beyond Docker Hub.

ARG INSTALLER_VERSION=2
FROM mlocati/php-extension-installer:${INSTALLER_VERSION} AS ext-installer

# ---------- Stage 1: Composer ----------
FROM php:8.4-cli-bookworm AS build

ARG REDIS_EXT_VERSION=6.2.0

COPY --from=ext-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions intl zip opcache sockets pcntl redis-${REDIS_EXT_VERSION} \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && APP_ENV=prod APP_DEBUG=0 php bin/console cache:warmup --no-interaction \
    && APP_ENV=prod APP_DEBUG=0 php bin/console assets:install public --no-interaction --symlink --relative

# ---------- Stage 2: Runtime (FrankenPHP, PHP 8.4) ----------
FROM dunglas/frankenphp:1.13.2-php8.4-bookworm AS runtime

ARG REDIS_EXT_VERSION=6.2.0

COPY --from=ext-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions intl zip opcache sockets pcntl redis-${REDIS_EXT_VERSION} \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

ENV APP_ENV=prod \
    APP_DEBUG=0

# PHP INI (repo-managed tuning: opcache, realpath cache, 9s execution cap)
COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile
# Rebuild the compiled container from mounted config at startup (see script)
COPY docker/frankenphp/entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod 0755 /usr/local/bin/app-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/app-entrypoint.sh"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]

# App code + vendor + warmed prod cache
COPY --chown=www-data:www-data --from=build /app /app

EXPOSE 8080

USER www-data
