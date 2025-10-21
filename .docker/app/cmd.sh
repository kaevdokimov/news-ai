#!/usr/bin/env bash

composer install -o
/usr/local/bin/php bin/console cache:clear
/usr/local/bin/php bin/console doctrine:database:create --if-not-exists
/usr/local/bin/php bin/console doctrine:migrations:migrate -n --allow-no-migration
/usr/local/bin/php bin/console doctrine:fixtures:load -n --append

crond -f -d
php-fpm --nodaemonize

