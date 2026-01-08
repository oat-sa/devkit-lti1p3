# ---------- Stage 1: Composer ----------
FROM composer:2.8 AS composer
WORKDIR /app
COPY . .
RUN set -eux\
        composer install -n --no-dev --prefer-dist --optimize-autoloader

# ---------- Stage 2: Builder ----------
FROM unit:php8.4 as builder

ENV MAKEFLAGS="-j8"
ARG APCU_VERSION=5.1.24
ARG GRPC_VERSION=1.73.0
ARG PROTOBUF_VERSION=4.31.1
ARG REDIS_VERSION=6.2.0
# Install system dependencies for PHP extensions
RUN set -eux; \
    apt-get update; \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    autoconf build-essential pkg-config re2c \
    libicu-dev zlib1g-dev libzip-dev \
    libonig-dev libxml2-dev libpng-dev \
    libpq-dev libssl-dev; \
    rm -rf /var/lib/apt/lists/*
# Install PHP extensions
RUN set -eux; \
    docker-php-ext-configure intl; \
    docker-php-ext-install -j$(nproc) \
    intl mbstring opcache zip pcntl sockets \
    sysvmsg sysvsem sysvshm pdo pdo_pgsql calendar; \
    pecl channel-update pecl.php.net; \
    printf "\n" | pecl install \
    igbinary \
    redis-${REDIS_VERSION} \
    grpc-${GRPC_VERSION} \
    protobuf-${PROTOBUF_VERSION} \
    apcu-${APCU_VERSION}; \
    docker-php-ext-enable igbinary redis grpc protobuf apcu
# ---------- Stage 3: Final image ----------
FROM gcr.io/distroless/base-debian12
# Copy PHP runtime and extensions from the builder stage
COPY --from=builder /usr/local/bin/ /usr/local/bin/
COPY --from=builder /usr/local/lib/ /usr/local/lib/
COPY --from=builder /usr/local/etc/php/ /usr/local/etc/php/
# Copy the application code
WORKDIR /var/www/html
COPY --from=composer /app /var/www/html
# Copy Unit configuration and PHP settings
COPY unit.json /docker-entrypoint.d/config.json
COPY php.ini /usr/local/etc/php/php.ini
# Set the entrypoint
ENTRYPOINT ["/usr/local/bin/unitd", "--no-daemon", "--control", "unix:/var/run/control.unit.sock"]

EXPOSE 8080
