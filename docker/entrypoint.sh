#!/bin/sh
set -eu

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec frankenphp run --config /etc/caddy/Caddyfile
