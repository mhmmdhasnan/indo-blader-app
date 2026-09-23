# syntax=docker/dockerfile:1.7

# ---- Stage 1: build frontend assets ----
FROM node:22-alpine AS assets
WORKDIR /app
# Install deps from the lockfile alone first, so this (slow, network-bound)
# layer is only invalidated when package*.json actually change — not on
# every source edit, which is what COPY . . before npm ci used to do.
COPY package.json package-lock.json ./
# --mount=type=cache persists npm's download cache across builds even when
# this layer itself gets invalidated (i.e. package-lock.json actually
# changed) — only the newly added/changed packages hit the network, not a
# full re-download. Requires BuildKit (default on Docker 23+/buildx).
RUN --mount=type=cache,target=/root/.npm \
    npm ci --ignore-scripts
COPY . .
RUN npm run build

# ---- Stage 2: PHP application ----
FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
        libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev icu-dev oniguruma-dev sqlite-dev curl-dev \
        mysql-client \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql pdo_sqlite mbstring bcmath zip gd intl opcache curl pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Same reasoning as the npm layer above: install PHP deps from just the
# lockfile so this layer only re-downloads packages when composer.json/.lock
# actually change, not on every source edit. --no-scripts because
# post-autoload-dump runs `artisan package:discover`, which needs the full
# app (below) to exist first.
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-scripts --no-autoloader --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/framework/testing storage/logs storage/app/public bootstrap/cache \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && cp -r public public-src \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
