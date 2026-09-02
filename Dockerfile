FROM composer:2 AS composer

FROM dunglas/frankenphp:1-php8.5-bookworm AS vendor
WORKDIR /app
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions zip
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction
RUN cp .env.example .env \
    && php artisan wayfinder:generate --with-form --no-interaction \
    && rm .env

FROM node:24-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install --ignore-scripts
COPY resources ./resources
COPY public ./public
COPY components.json tsconfig.json vite.config.ts ./
COPY --from=vendor /app/resources/js/actions ./resources/js/actions
COPY --from=vendor /app/resources/js/routes ./resources/js/routes
COPY --from=vendor /app/resources/js/wayfinder ./resources/js/wayfinder
ENV WAYFINDER_COMMAND=true
RUN npm run build

FROM dunglas/frankenphp:1-php8.5-bookworm
WORKDIR /app

RUN install-php-extensions pdo_pgsql intl zip opcache pcntl

COPY --from=vendor --chown=www-data:www-data /app /app
COPY --from=frontend --chown=www-data:www-data /app/public/build /app/public/build
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/financeiro-entrypoint

RUN chmod +x /usr/local/bin/financeiro-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
ENTRYPOINT ["financeiro-entrypoint"]
