#!/bin/sh
set -e

# Set default environment variables
: ${APP_ENV:=prod}
: ${APP_DEBUG:=0}

# Change to the app directory
cd /var/www/app

# Install dependencies if vendor directory is missing
if [ ! -d "vendor" ]; then
    composer install --prefer-dist --no-dev --no-scripts --no-progress --optimize-autoloader --no-interaction
fi

# Run database migrations
if [ "$APP_ENV" = "prod" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

# Clear and warm up the cache
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

# Set proper permissions
chown -R www-data:www-data /var/www/app/var
chmod -R 0777 /var/www/app/var

# Start supervisord
exec "$@"
