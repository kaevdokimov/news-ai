#!/bin/sh
set -e

# If running as root, start cron and keep root for supervisord (programs drop to www-data)
if [ "$(id -u)" = '0' ]; then
    # Start cron in the background
    /usr/sbin/crond -L /var/log/cron/cron.log &
fi

# Execute the CMD from the Dockerfile (supervisord will run as root; programs use user=www-data)
exec "$@"
