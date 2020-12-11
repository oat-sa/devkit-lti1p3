

#{{{ fpm
FROM php:7.4-fpm-alpine as fpm

RUN set -eux; \
        apk add --no-cache --virtual .build-deps \
        autoconf \
        g++ \
        jpeg-dev \
        icu-dev \
        libmcrypt-dev \
        libpng-dev \
        libxml2-dev \
        linux-headers \
        make \
        oniguruma-dev \
        procps \
        unzip \
        wget \
        zip \
        libzip-dev \
        zlib-dev \
        zstd-dev \
        ; \
        yes "" | pecl install redis igbinary \
        ;\
        docker-php-ext-install intl; \
        docker-php-ext-install gd; \
        docker-php-ext-install opcache; \
        docker-php-ext-install zip; \
        docker-php-ext-install calendar; \
        \
        docker-php-ext-enable redis; \
        docker-php-ext-enable igbinary; \
        runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
        | tr ',' '\n' \
        | sort -u \
        | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
        )"; \
        apk add --no-cache $runDeps; \
        apk del --no-network .build-deps; \
        rm -rf /var/www/html; \
        chmod 0777 /tmp/;

#}}} fpm

#{{{ composer
FROM composer:2 AS composer

WORKDIR /var/www/html
COPY . .

# Install required packages
RUN set -eux; \
        composer install -n --optimize-autoloader --no-dev --prefer-dist; \
        composer dump-autoload -n --optimize --no-dev --classmap-authoritative; \
        rm -rf .build/;
#}}} composer

FROM fpm

WORKDIR /var/www/html
COPY --from=composer /var/www/html /var/www/html
