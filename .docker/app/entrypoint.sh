#!/bin/sh
set -e

# Start cron in the background as root
/usr/sbin/crond -f -L /var/log/cron/cron.log &

# Switch to www-data user if not already
if [ "$(id -u)" = '0' ]; then
    exec su-exec www-data "$0" "$@"
fi

# Execute the CMD from the Dockerfile
exec "$@"
