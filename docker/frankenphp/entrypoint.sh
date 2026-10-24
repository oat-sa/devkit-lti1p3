#!/bin/sh
#
# App entrypoint wrapper for the FrankenPHP runtime images.
#
# The Dockerfiles compile the Symfony container at BUILD time (cache:warmup)
# from the repository's default configuration. Kubernetes deployments override
# that configuration at RUNTIME via mounted files (e.g. the lti1p3.yaml
# ConfigMap), and Symfony never rebuilds an existing prod container on its
# own — without this step the app would silently serve the baked demo config
# instead of the mounted one.
#
# Clear the compiled container so the first boot rebuilds it from whatever
# config files are actually mounted. `--no-warmup` keeps boot fast; the first
# request pays the one-off compile cost instead.
set -e

if [ "${APP_CACHE_REBUILD_ON_BOOT:-1}" = "1" ] && [ -d /app/var/cache/prod ]; then
    php /app/bin/console cache:clear --no-warmup --env=prod 2>/dev/null \
        || rm -rf /app/var/cache/prod
fi

exec docker-php-entrypoint "$@"
