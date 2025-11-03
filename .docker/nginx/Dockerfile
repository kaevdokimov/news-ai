FROM nginx:1.29-alpine3.22

# Копируем конфигурацию Nginx
COPY ./.docker/nginx/app.conf /etc/nginx/conf.d/default.conf

# Убедимся, что директория для certbot существует
RUN mkdir -p /var/www/certbot

# Запускаем Nginx
CMD ["nginx", "-g", "daemon off;"]
