#!/bin/sh
set -e

# Only run app setup when starting the web server (not for one-off commands)
if [ "$1" = "apache2-foreground" ]; then
    if [ -z "$APP_KEY" ]; then
        echo "ERROR: APP_KEY is not set. Run:"
        echo "  docker compose run --rm app php artisan key:generate --show"
        echo "and put the value in .env as APP_KEY=..."
        exit 1
    fi

    mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
    chown -R www-data:www-data storage bootstrap/cache

    php artisan migrate --force
    php artisan storage:link 2>/dev/null || true
fi

exec docker-php-entrypoint "$@"
