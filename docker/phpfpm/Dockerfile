FROM php:8-fpm

RUN apt-get update && \
    apt-get install -y libpng-dev libjpeg-dev libpq-dev libzip-dev zip unzip sudo wget zlib1g-dev libicu-dev libzstd-dev g++ && \
    rm -rf /var/lib/apt/lists/*

RUN yes | pecl install igbinary redis

RUN docker-php-ext-install intl && \
    docker-php-ext-install gd && \
    docker-php-ext-install opcache && \
    docker-php-ext-install zip && \
    docker-php-ext-install calendar && \
    docker-php-ext-install sockets && \
    docker-php-ext-enable igbinary && \
    docker-php-ext-enable redis

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

RUN rm -rf /var/www/html \
    && chmod 0777 /tmp/
