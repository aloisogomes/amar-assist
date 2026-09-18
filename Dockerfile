# syntax=docker/dockerfile:1

FROM php:8.4-cli-bookworm AS backend

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    bcmath \
    exif \
    gd \
    intl \
    mbstring \
    pcntl \
    pdo_mysql \
    pdo_sqlite \
    redis \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

COPY docker/php-entrypoint.sh /usr/local/bin/php-entrypoint
RUN chmod +x /usr/local/bin/php-entrypoint

COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY backend/ ./
RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["php-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY docker/node-entrypoint.sh /usr/local/bin/node-entrypoint
RUN chmod +x /usr/local/bin/node-entrypoint

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ ./

EXPOSE 5173

ENTRYPOINT ["node-entrypoint"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]
