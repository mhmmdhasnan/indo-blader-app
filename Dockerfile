# ---- Stage 1: build frontend assets ----
FROM node:22-alpine AS assets
WORKDIR /app
COPY . .
RUN npm ci --ignore-scripts && npm run build

# ---- Stage 2: PHP application ----
FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
        libpng-dev libjpeg-turbo-dev freetype-dev \
        libzip-dev icu-dev oniguruma-dev sqlite-dev \
        mysql-client \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo pdo_mysql pdo_sqlite mbstring bcmath zip gd intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/framework/testing storage/logs storage/app/public bootstrap/cache \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && cp -r public public-src \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
