#!/usr/bin/env bash

/usr/local/bin/php bin/console cache:clear
/usr/local/bin/php bin/console doctrine:migrations:migrate -n --allow-no-migration
/usr/local/bin/php bin/console doctrine:fixtures:load -n --append

crond -f -d
php-fpm --nodaemonize

