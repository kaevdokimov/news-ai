FROM ghcr.io/kaevdokimov/php:php84-dev

# Build arguments
ARG APP_DIR=/var/www/app

# Environment variables
ENV APP_DIR=${APP_DIR}

# Copy application files
COPY --chown=www-data:staff ./ ${APP_DIR}
COPY ./.docker/app/php/fpm/php.ini /usr/local/etc/php/php.ini

# Setup cron for news parsing
#RUN echo "*/5 * * * *  php /var/www/app/bin/console app:parse-rss --async" | crontab - && \
#    mkdir -p /var/log && \
#    touch /var/log/news_parsing.log && \
#    chown www-data:staff /var/log/news_parsing.log

RUN chmod +x ${APP_DIR}/.docker/app/cmd.sh

WORKDIR ${APP_DIR}
USER www-data
CMD ["/var/www/app/.docker/app/cmd.sh"]
