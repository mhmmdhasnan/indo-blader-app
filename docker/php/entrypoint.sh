#!/bin/sh
set -e

# Only storage/app/public and storage/logs are bind-mounted from the host
# (see docker-compose.yml) — that reset ownership to the host user on every
# start, so fix it back up before anything tries to write there. Everything
# else under storage/ (framework/*, i.e. view cache) lives inside the image
# and keeps the ownership set at build time, so it doesn't need re-chowning
# here — that's what used to make every container start slow when the whole
# storage/ tree was bind-mounted and re-chowned recursively.
chown -R www-data:www-data \
    /var/www/html/storage/app/public \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

# public/ is a shared volume with the webserver container and starts empty,
# so restore the assets built into the image on every start.
if [ -d /var/www/html/public-src ]; then
    cp -r /var/www/html/public-src/. /var/www/html/public/
fi

[ -f /var/www/html/.env ] || cp /var/www/html/.env.example /var/www/html/.env

READY_FILE=/var/www/html/storage/.docker-ready

if [ "$CONTAINER_ROLE" = "queue" ] || [ "$CONTAINER_ROLE" = "reverb" ]; then
    # Let the app container own migrations/caching so containers don't race.
    echo "Waiting for app container to finish setup..."
    until [ -f "$READY_FILE" ]; do
        sleep 2
    done
else
    if ! grep -q "^APP_KEY=base64" /var/www/html/.env 2>/dev/null; then
        php artisan key:generate --force --no-interaction
    fi

    RETRIES=30
    until php artisan migrate --force --no-interaction; do
        RETRIES=$((RETRIES - 1))
        if [ "$RETRIES" -le 0 ]; then
            echo "Database not reachable, giving up."
            exit 1
        fi
        echo "Database not ready yet, retrying in 2s... ($RETRIES left)"
        sleep 2
    done

    php artisan storage:link || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    touch "$READY_FILE"
fi

# Re-fix ownership: the commands above ran as root and may have created new
# files (caches, the ready marker, log entries) that php-fpm's www-data
# worker needs to read or overwrite later.
chown -R www-data:www-data \
    /var/www/html/storage/app/public \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

exec "$@"
