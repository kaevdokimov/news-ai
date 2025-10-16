FROM php:8.4-fpm-alpine3.22

# Установка системных зависимостей и расширений PHP
RUN apk update && apk upgrade && apk add --no-cache \
    git unzip wget sudo openssh-client bash \
    icu-dev libxml2-dev gmp-dev libpng-dev libzip-dev postgresql-dev rabbitmq-c-dev libssh-dev \
    gettext-dev linux-headers \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        gettext \
        gd \
        gmp \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        sockets \
        zip \
        sysvsem \
    && docker-php-ext-enable sysvsem \
    && rm -rf /tmp/* /var/cache/apk/*

# Установка PECL расширений
RUN pecl install -o -f \
        apcu \
        amqp \
        igbinary \
        redis \
    && docker-php-ext-enable \
        apcu \
        amqp \
        igbinary \
        redis

# Установка и настройка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer clear-cache

# Настройка пользователя www-data
RUN chmod g+w /usr/local/etc/php/conf.d/ \
    && deluser www-data || true \
    && delgroup staff || true \
    && addgroup -g 1001 staff \
    && adduser -D -u 1000 -G staff www-data \
    && chown www-data:staff /var/www \
    && echo 'www-data ALL=(ALL:ALL) NOPASSWD: ALL' > /etc/sudoers.d/www-data

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
RUN deluser www-data && delgroup staff && \
    addgroup -g ${GID} staff && \
    adduser -D -u ${UID} -G staff www-data && \
    chown -R www-data:staff /var/www

# Установка пользователя и команды по умолчанию
USER www-data
CMD ["/var/www/app/.docker/app/cmd.sh"]
