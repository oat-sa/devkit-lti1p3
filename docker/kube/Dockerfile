#{{{ fpm
FROM europe-west1-docker.pkg.dev/tao-artefacts/base-images/php-v8.3:v1.0.7 as fpm

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
        composer self-update 2.6.6; # TODO update symfony/flex to the latest version instead \
        composer install -n --optimize-autoloader --no-dev --prefer-dist; \
        composer dump-autoload -n --optimize --no-dev --classmap-authoritative; \
        rm -rf .build/;
#}}} composer

FROM fpm

WORKDIR /var/www/html
COPY --from=composer /var/www/html /var/www/html
