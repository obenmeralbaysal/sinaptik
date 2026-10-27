#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true

if [ -z "${APP_KEY:-}" ]; then
    echo "[entrypoint] APP_KEY empty, generating..."
    php artisan key:generate --force || true
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force || true
fi

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache  || true
    php artisan route:cache   || true
    php artisan view:cache    || true
fi

php artisan storage:link || true

exec "$@"
