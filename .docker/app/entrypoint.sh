#!/bin/sh
set -e

# If running as root, start cron and drop privileges to www-data
if [ "$(id -u)" = '0' ]; then
    # Start cron in the background
    /usr/sbin/crond -L /var/log/cron/cron.log &
    # Re-exec this script as www-data to run the final CMD
    exec su-exec www-data "$0" "$@"
fi

# Running as non-root: execute the CMD from the Dockerfile
exec "$@"
