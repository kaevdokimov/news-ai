FROM cr.yandex/crpoc9esj8m86e03jc1m/env/php:8-4-fpm

LABEL maintainer="aleksey@kolyadin.ru"
LABEL maintainer="Kirill.Evdokimov@easycomm.ru"

# Переменные
ARG UID=1000
ARG GID=1001
ENV TZ=Europe/Moscow APP_DIR=/var/www/app PHP_CONF_DIR=/usr/local/etc/php/conf.d

# Создание директорий и файлов конфигурации
RUN mkdir -p $PHP_CONF_DIR && touch $PHP_CONF_DIR/custom.ini

# Копирование файлов
COPY --chown=www-data:www-data ./ $APP_DIR
COPY ./.docker/app/php/fpm/php.ini /usr/local/etc/php/php.ini

# Удаление Xdebug
RUN pecl uninstall xdebug || true && \
    rm -f $PHP_CONF_DIR/docker-php-ext-xdebug.ini $PHP_CONF_DIR/xdebug.ini

# Установка временной зоны и PHP-настроек
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && \
    echo $TZ > /etc/timezone && \
    echo "date.timezone=$TZ" > $PHP_CONF_DIR/custom.ini && \
    rm -rf $APP_DIR/var/cache/* /var/www/app/var/log/* && \
    chmod +x $APP_DIR/.docker/app/cmd.sh

# Установка рабочей директории
WORKDIR $APP_DIR

# Изменение UID и GID пользователя www-data
RUN usermod --uid ${UID} www-data && groupmod --gid $GID www-data

# Установка пользователя и команды по умолчанию
USER www-data
CMD ["/var/www/app/.docker/app/cmd.sh"]
