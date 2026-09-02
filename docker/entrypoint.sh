#!/bin/sh
set -eu

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

if [ -z "${APP_KEY:-}" ]; then
    echo "Defina APP_KEY. Gere com: echo \"base64:\$(openssl rand -base64 32)\"" >&2
    exit 1
fi

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

php artisan migrate --force --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec frankenphp run --config /etc/caddy/Caddyfile
