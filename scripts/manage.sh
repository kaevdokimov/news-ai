#!/bin/bash

# Скрипт для управления системой News AI

case "$1" in
    start)
        echo "🚀 Запуск системы News AI..."
        docker compose up -d
        sleep 10
        docker exec news_ai_app php bin/console messenger:consume async --memory-limit=128M --time-limit=3600
        echo "✅ Система запущена!"
        echo "🌐 Админ-панель: http://localhost:81/admin"
        echo "🐰 RabbitMQ: http://localhost:15673 (guest/guest)"
        ;;
    stop)
        echo "🛑 Остановка системы News AI..."
        docker compose down
        echo "✅ Система остановлена!"
        ;;
    restart)
        echo "🔄 Перезапуск системы News AI..."
        docker compose down
        docker compose up -d
        sleep 10
        docker exec news_ai_app php bin/console messenger:consume async --memory-limit=128M --time-limit=3600
        echo "✅ Система перезапущена!"
        ;;
    status)
        ./scripts/monitor.sh
        ;;
    parse)
        echo "📰 Запуск парсинга новостей..."
        docker exec news_ai_app php bin/console app:parse-rss --async
        echo "✅ Парсинг запущен асинхронно!"
        ;;
    worker)
        echo "⚙️  Запуск воркера очередей..."
        docker exec news_ai_app php bin/console messenger:consume async --memory-limit=128M --time-limit=3600
        echo "✅ Воркер запущен!"
        ;;
    logs)
        echo "📋 Логи приложения:"
        docker exec news_ai_app tail -f var/log/dev.log
        ;;
    install)
        echo "📦 Установка зависимостей и настройка БД..."
        docker exec news_ai_app composer install
        docker exec news_ai_app php bin/console doctrine:migrations:migrate --no-interaction
        docker exec news_ai_app php bin/console doctrine:fixtures:load --no-interaction
        echo "✅ Установка завершена!"
        ;;
    cron)
        echo "⏰ Настройка cron для автоматического парсинга..."
        echo "*/5 * * * * docker exec news_ai_app php bin/console app:parse-rss --async >> /tmp/news_parsing.log 2>&1" | crontab -
        echo "✅ Cron настроен! Парсинг будет запускаться каждые 5 минут."
        ;;
    *)
        echo "📖 Управление системой News AI"
        echo
        echo "Использование: $0 {start|stop|restart|status|parse|worker|logs|install|cron}"
        echo
        echo "Команды:"
        echo "  start    - Запустить систему"
        echo "  stop     - Остановить систему"
        echo "  restart  - Перезапустить систему"
        echo "  status   - Показать статус системы"
        echo "  parse    - Запустить парсинг новостей"
        echo "  worker   - Запустить воркер очередей"
        echo "  logs     - Показать логи приложения"
        echo "  install  - Установить зависимости и настроить БД"
        echo "  cron     - Настроить cron для автоматического парсинга"
        echo
        echo "Примеры:"
        echo "  $0 start     # Запустить систему"
        echo "  $0 status    # Проверить статус"
        echo "  $0 parse     # Запустить парсинг"
        echo "  $0 cron      # Настроить автоматический парсинг"
        ;;
esac
