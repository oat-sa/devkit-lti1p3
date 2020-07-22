#{{{ lti-base
FROM php:7.4-fpm-alpine as lti-base
ENV MAKEFLAGS "-j8"

RUN set -eux; \
        cd /; \
        apk add --no-cache --virtual .build-deps \
            autoconf \
            g++ \
            icu-dev \
            libpng-dev \
            unzip \
            wget \
            zip \
            libzip-dev \
            zlib-dev \
            zstd-dev \
            jpeg-dev \
            libpq \
            make \
        ; \
        yes "" | pecl install igbinary redis \
        ;\
        docker-php-ext-install intl; \
        docker-php-ext-install gd; \
        docker-php-ext-install opcache; \
        docker-php-ext-install zip; \
        docker-php-ext-install calendar; \
        docker-php-ext-install sockets \
        ; \
        docker-php-ext-enable igbinary; \
        docker-php-ext-enable redis; \
        runDeps="$( \
                scanelf --needed --nobanner --format '%n#p' --recursive /usr/local \
                | tr ',' '\n' \
                | sort -u \
                | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }' \
        )"; \
        apk add --no-cache $runDeps; \
        apk del --no-network .build-deps; \
        rm -rf /var/www/html

WORKDIR /var/www/html
#}}} lti-base

#{{{ composer
FROM lti-base AS composer


COPY . /app
WORKDIR /app
RUN set -eu; \
        EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"; \
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"; \
        ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"; \
        if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; \
        then \
            >&2 echo 'ERROR: Invalid installer checksum'; \
            rm composer-setup.php; \
            exit 1; \
        fi; \
        php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer; \
        RESULT=$?; \
        rm composer-setup.php;

# Install required packages
RUN set -eu; \
        composer install -n --optimize-autoloader --no-dev; \
        composer dump-autoload -n --optimize --no-dev --classmap-authoritative;
#}}} composer


FROM lti-base as prod

COPY --from=composer /app .
COPY --from=composer /app /app
COPY ./docker/phpfpm/www.conf /usr/local/etc/php-fpm.d/zz-docker.conf
RUN set -eux; \
        { \
            echo 'opcache.memory_consumption=256'; \
            echo 'opcache.interned_strings_buffer=8'; \
            echo 'opcache.max_accelerated_files=20000'; \
            echo 'opcache.revalidate_freq=2'; \
            echo 'opcache.fast_shutdown=1'; \
            echo 'opcache.enable_cli=1'; \
            echo 'opcache.load_comments=1'; \
            echo 'opcache.validate_timestamps=0'; \
            echo 'realpath_cache_size=4096K'; \
            echo 'realpath_cache_ttl=600'; \
        } >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
        ; \
        mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini; \
        rm /usr/local/etc/php-fpm.d/www* \
        ; \
        chown -R www-data: /var/www/html/