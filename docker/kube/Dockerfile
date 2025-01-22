#{{{ fpm
FROM europe-west1-docker.pkg.dev/tao-artefacts/base-images/php-v8.3:v1.0.9 as fpm

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
FROM fpm as composer
COPY --from=composer:2.6.6 /usr/bin/composer /usr/local/bin/composer

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
