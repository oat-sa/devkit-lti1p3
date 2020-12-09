#{{{ demo_lti1p3
FROM php:7.4-fpm-alpine as demo_lti1p3-base
ENV MAKEFLAGS "-j8"

RUN set -eux; \
        cd /; \
        apk add --no-cache --virtual .build-deps \
            autoconf \
            g++ \
            unzip \
            icu-dev \
            sudo \
            libmcrypt-dev \
            libpng-dev \
            librdkafka-dev \
            libxml2-dev \
            linux-headers \
            make \
            oniguruma-dev \
            postgresql-dev \
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
        docker-php-ext-install sockets \
        ; \
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

RUN set -eux; \
        { \
            echo 'upload_max_filesize=60M'; \
            echo 'post_max_filesize=60M'; \
            echo 'post_max_size=60M'; \
        } >> /usr/local/etc/php/conf.d/deliver.ini \
        ;

WORKDIR /var/www/html
#}}} demo_lti1p3-base

#{{{ composer
FROM demo_lti1p3-base AS composer

ARG SSH_KEY
ENV SSH_KEY=$SSH_KEY

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
        rm composer-setup.php; \
        apk --no-cache add openssh git; \
        composer self-update --1; \
        composer global require hirak/prestissimo;

WORKDIR /app
COPY . .

# Install required packages
RUN set -eu; \
        mkdir -p ~/.ssh; \
        echo -e "${SSH_KEY}" > ~/.ssh/id_rsa; \
        chmod og-rwx ~/.ssh/id_rsa; \
        ssh-keyscan -H github.com >> ~/.ssh/known_hosts \
        ; \
        composer install -n --optimize-autoloader --no-dev --prefer-dist; \
        composer dump-autoload -n --optimize --no-dev --classmap-authoritative; \
        rm -rf .build/; \
        rm -rf .git; \
        rm -rf ~/.ssh/;
#}}} composer
