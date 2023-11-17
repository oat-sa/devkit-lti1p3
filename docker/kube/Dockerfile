#{{{ fpm
FROM php:8.1.8-fpm-alpine3.16 as fpm

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
        yes "" | pecl update-channels \
        ; \
        yes "" | pecl install redis igbinary \
        ;\
        docker-php-ext-install intl gd opcache zip calendar; \
        \
        docker-php-ext-enable redis igbinary; \
        runDeps="$( \
        scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
        | tr ',' '\n' \
        | sort -u \
        | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
        )"; \
        apk add --no-cache $runDeps; \
        apk del --no-network .build-deps; \
        apk upgrade; \
        chmod 0777 /tmp/;

RUN set -eux; \
        { \
            echo 'opcache.preload=/var/www/html/config/preload.php'; \
            echo 'opcache.preload_user=www-data'; \
            echo 'opcache.memory_consumption=256'; \
            echo 'opcache.max_accelerated_files=20000'; \
            echo 'opcache.validate_timestamps=0'; \
            echo 'realpath_cache_size=4096K'; \
            echo 'realpath_cache_ttl=600'; \
        } >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
        ;
#}}} fpm


#{{{ composer
FROM composer:2 AS composer

WORKDIR /var/www/html
COPY . .

# Install required packages
RUN set -eux; \
        composer self-update --2; \
        composer install -n --optimize-autoloader --no-dev --prefer-dist; \
        composer dump-autoload -n --optimize --no-dev --classmap-authoritative; \
        rm -rf .build/;
#}}} composer

FROM fpm

WORKDIR /var/www/html
COPY --from=composer /var/www/html /var/www/html
