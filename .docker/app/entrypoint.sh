#!/bin/bash
set -e

# Navigate to the application directory
cd /var/www/app

# Debug: List files in the current directory
echo "Current directory: $(pwd)"
ls -la

# Install dependencies if vendor directory doesn't exist
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --prefer-dist --no-scripts --no-progress --no-interaction
fi

# Check if bin/console exists
if [ ! -f "bin/console" ]; then
    echo "Error: bin/console not found in $(pwd)" >&2
    ls -la bin/ 2>/dev/null || echo "bin/ directory does not exist"
    exit 1
fi

# Run database migrations
echo "Running database migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Clear and warm up the cache
echo "Clearing and warming up cache..."
php bin/console cache:clear
php bin/console cache:warmup

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data var public
chmod -R 0777 var

# Start the application
echo "Starting the application..."
exec "$@"
