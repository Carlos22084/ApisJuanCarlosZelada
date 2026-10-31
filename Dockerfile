FROM node:22-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php:8.2-cli-bookworm AS application

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        git \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath curl exif gd mbstring pdo_mysql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --no-progress

COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/99-aya.ini

RUN composer dump-autoload --no-dev --optimize \
    && php artisan package:discover --ansi \
    && chmod +x scripts/render-start.sh \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["scripts/render-start.sh"]
