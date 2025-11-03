#!/bin/bash
set -e

# Enable debug output
set -x

# Navigate to the application directory
cd /var/www/app

# Debug: List files in the current directory
echo "=== Current directory: $(pwd) ==="
ls -la

# Check if we're in the correct directory
if [ ! -f "composer.json" ]; then
    echo "ERROR: composer.json not found in $(pwd)" >&2
    echo "Current directory contents:" >&2
    ls -la >&2
    exit 1
fi

# Install dependencies if vendor directory doesn't exist
if [ ! -d "vendor" ]; then
    echo "=== Installing Composer dependencies... ==="
    composer install --prefer-dist --no-scripts --no-progress --no-interaction --optimize-autoloader
else
    echo "=== Vendor directory exists, skipping composer install ==="
fi

# Verify bin/console exists
if [ ! -f "bin/console" ]; then
    echo "ERROR: bin/console not found in $(pwd)" >&2
    echo "Available files in bin/:" >&2
    ls -la bin/ 2>/dev/null || echo "bin/ directory does not exist" >&2
    echo "Current directory contents:" >&2
    ls -la >&2
    exit 1
fi

# Wait for database to be ready
echo "=== Waiting for database to be ready... ==="
until pg_isready -h news_ai_pg -p 5432 -U ${POSTGRES_USER} -d ${POSTGRES_DB} > /dev/null 2>&1; do
    echo "Waiting for PostgreSQL to be ready..."
    sleep 2
done

# Run database migrations
echo "=== Running database migrations... ==="
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Clear and warm up the cache
echo "=== Clearing and warming up cache... ==="
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

# Set proper permissions
echo "=== Setting permissions... ==="
chown -R www-data:www-data var public
chmod -R 0777 var

# Start the application
echo "=== Starting the application... ==="
exec "$@"
