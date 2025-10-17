# Base image arguments
ARG PHP_VERSION=8.4
ARG ALPINE_VERSION=3.22

# Build stage
FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} AS builder

# Build arguments
ARG UID=1000
ARG GID=1001
ARG TZ=Europe/Moscow
ARG APP_DIR=/var/www/app
ARG PHP_CONF_DIR=/usr/local/etc/php/conf.d

# Environment variables
ENV TZ=${TZ} \
    APP_DIR=${APP_DIR} \
    PHP_CONF_DIR=${PHP_CONF_DIR}

# System dependencies and PHP extensions installation
RUN apk update && apk upgrade && \
    apk add --no-cache \
        git unzip wget sudo openssh-client bash dcron \
        icu-dev libxml2-dev gmp-dev libpng-dev libzip-dev \
        postgresql-dev rabbitmq-c-dev libssh-dev gettext-dev \
        linux-headers php84-dev autoconf gcc g++ make \
        pcre-dev zlib-dev && \
    docker-php-ext-install -j$(nproc) \
        bcmath gettext gd gmp intl opcache pcntl \
        pdo_pgsql sockets zip sysvsem && \
    docker-php-ext-enable sysvsem && \
    # Install PECL extensions with build deps
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS && \
    pecl install -o -f apcu amqp igbinary redis && \
    docker-php-ext-enable apcu amqp igbinary redis && \
    # Cleanup
    apk del .build-deps && \
    rm -rf /tmp/* /var/cache/apk/*

# Composer installation
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN composer clear-cache

# User configuration
RUN mkdir -p ${PHP_CONF_DIR} && \
    touch ${PHP_CONF_DIR}/custom.ini && \
    ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime && \
    echo ${TZ} > /etc/timezone && \
    echo "date.timezone=${TZ}" > ${PHP_CONF_DIR}/custom.ini && \
    deluser www-data || true && \
    delgroup staff || true && \
    addgroup -g ${GID} staff && \
    adduser -D -u ${UID} -G staff www-data && \
    chown www-data:staff /var/www && \
    echo 'www-data ALL=(ALL:ALL) NOPASSWD: ALL' > /etc/sudoers.d/www-data

# Application setup
RUN mkdir -p ${PHP_CONF_DIR} && \
    touch ${PHP_CONF_DIR}/custom.ini && \
    ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime && \
    echo ${TZ} > /etc/timezone && \
    echo "date.timezone=${TZ}" > ${PHP_CONF_DIR}/custom.ini

# Copy application files
COPY --chown=www-data:staff ./ ${APP_DIR}
COPY ./.docker/app/php/fpm/php.ini /usr/local/etc/php/php.ini

# Setup cron for news parsing
RUN echo "*/5 * * * *  php /var/www/app/bin/console app:parse-rss --async" | crontab - && \
    mkdir -p /var/log && \
    touch /var/log/news_parsing.log && \
    chown www-data:staff /var/log/news_parsing.log

# Cleanup and permissions
RUN rm -rf ${APP_DIR}/var/cache/* /var/www/app/var/log/* && \
    chmod +x ${APP_DIR}/.docker/app/cmd.sh && \
    chown -R www-data:staff /var/www

WORKDIR ${APP_DIR}
USER www-data
CMD ["/var/www/app/.docker/app/cmd.sh"]
