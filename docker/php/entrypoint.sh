#!/bin/sh
set -e

# storage/ and bootstrap/cache are bind-mounted from the host, which resets
# ownership to the host user on every start — php-fpm runs as www-data, so
# fix it back up before anything tries to write there.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# public/ is a shared volume with the webserver container and starts empty,
# so restore the assets built into the image on every start.
if [ -d /var/www/html/public-src ]; then
    cp -r /var/www/html/public-src/. /var/www/html/public/
fi

[ -f /var/www/html/.env ] || cp /var/www/html/.env.example /var/www/html/.env

READY_FILE=/var/www/html/storage/.docker-ready

if [ "$CONTAINER_ROLE" = "queue" ]; then
    # Let the app container own migrations/caching so both containers don't race.
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
# files (caches, the ready marker) that php-fpm's www-data worker needs to
# read or overwrite later.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

exec "$@"
