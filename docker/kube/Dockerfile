# ---------- Stage 1: Composer ----------
FROM composer:2.8 AS composer
WORKDIR /app
COPY . .
RUN set -eux;
        composer install -n --no-dev --prefer-dist --optimize-autoloader;
#         composer dump-autoload -n --optimize --classmap-authoritative; \
#         rm -rf .build/

# ---------- Stage 2: Runtime (NGINX Unit + PHP 8.4) ----------
# Tip: adjust tag to whatever Unit PHP 8.4 tag you use in your registry (e.g. unit:1.34.2-php8.4)
FROM unit:php8.4

ENV MAKEFLAGS="-j8"

# Pin PECL versions to match your current image
ARG APCU_VERSION=5.1.24
ARG GRPC_VERSION=1.73.0
ARG PROTOBUF_VERSION=4.31.1
ARG REDIS_VERSION=6.2.0

# System deps for building PHP extensions
RUN set -eux; \
        apt-get update; \
        DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        ca-certificates curl unzip zip git \
        autoconf build-essential pkg-config re2c \
        libicu-dev zlib1g-dev libzip-dev \
        libonig-dev libxml2-dev libpng-dev \
        libpq-dev libssl-dev; \
        rm -rf /var/lib/apt/lists/*

# Core PHP extensions (docker-php-* helpers are available in Unit’s PHP images)
RUN set -eux; \
        docker-php-ext-configure intl; \
        docker-php-ext-install -j8 \
        intl mbstring opcache zip pcntl sockets \
        sysvmsg sysvsem sysvshm pdo pdo_pgsql calendar

# PECL extensions (pinned)
RUN set -eux; \
        pecl channel-update pecl.php.net; \
        printf "\n" | pecl install \
        igbinary \
        redis-${REDIS_VERSION} \
        grpc-${GRPC_VERSION} \
        protobuf-${PROTOBUF_VERSION} \
        apcu-${APCU_VERSION}; \
        docker-php-ext-enable igbinary redis grpc protobuf apcu

# PHP INI (keeps your current tuning, incl. the GRPC stack-size workaround)
RUN set -eux; \
        { \
        echo 'opcache.preload=/var/www/html/config/preload.php'; \
        echo 'opcache.preload_user=www-data'; \
        echo 'opcache.memory_consumption=256'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'realpath_cache_size=4096K'; \
        echo 'realpath_cache_ttl=600'; \
        } > /usr/local/etc/php/conf.d/zzz-opcache.ini; \
        echo 'zend.max_allowed_stack_size=-1' > /usr/local/etc/php/conf.d/zend.ini

# App code
WORKDIR /var/www/html
COPY --from=composer /app /var/www/html

# Unit config: drop this file into /docker-entrypoint.d so it’s auto-applied at start
COPY unit.json /docker-entrypoint.ed/config.json
COPY php.ini /etc/unit-php/php.ini
RUN test -s /docker-entrypoint.d/config.json \
        && grep -q '"listeners"' /docker-entrypoint.d/config.json
EXPOSE 8080
# Unit’s official entrypoint will start unitd and apply any configs in /docker-entrypoint.d/
