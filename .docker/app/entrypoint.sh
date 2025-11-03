#!/bin/bash
set -e
set -o pipefail

# Set default environment variables
: ${APP_ENV:=prod}
: ${APP_DEBUG:=0}

# Change to the app directory
cd /var/www/app || { echo "Failed to change to /var/www/app"; exit 1; }

# Verify vendor directory exists
if [ ! -d "vendor" ]; then
    composer install --prefer-dist --no-scripts --no-progress --no-interaction
fi

# Copy .env file if it doesn't exist
if [ ! -f ".env" ] && [ -f ".env.dist" ]; then
    cp .env.dist .env
fi

# Generate APP_SECRET if not set
if [ -z "${APP_SECRET}" ]; then
    export APP_SECRET=$(openssl rand -hex 32)
    echo "Generated APP_SECRET: ${APP_SECRET}"
fi

# Run post-install scripts on first run
if [ ! -f "var/installed" ]; then
    echo "First run - running post-install scripts"
    # Сначала создаем кеш, чтобы не было проблем с правами
    php bin/console cache:warmup --no-optional-warmers
    # Затем запускаем post-install скрипты
    composer run-script post-install-cmd
    touch var/installed
fi

# Run database migrations
if [ "$APP_ENV" = "prod" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

# Clear and warm up the cache
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

# Set proper permissions
chown -R www-data:www-data var public
chmod -R 0777 var

# Start supervisord
exec "$@"
