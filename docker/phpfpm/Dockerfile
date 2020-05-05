FROM php:7.4-fpm

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

RUN rm -rf /var/www/html \
    && chmod 0777 /tmp/
