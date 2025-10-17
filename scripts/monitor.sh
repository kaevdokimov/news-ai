#!/bin/bash

# Скрипт для мониторинга системы новостей

echo "=== Мониторинг системы News AI ==="
echo "Дата: $(date)"
echo

# Проверяем статус контейнеров
echo "📦 Статус контейнеров:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep news_ai
echo

# Проверяем количество источников новостей
echo "📰 Источники новостей:"
docker exec news_ai_app php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM news_sources WHERE is_active = true" | grep -v "array"
echo

# Проверяем количество новостей
echo "📄 Общее количество новостей:"
docker exec news_ai_app php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM news_items" | grep -v "array"
echo

# Проверяем новости за последние 24 часа
echo "🕐 Новости за последние 24 часа:"
docker exec news_ai_app php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM news_items WHERE created_at >= NOW() - INTERVAL '24 hours'" | grep -v "array"
echo

# Проверяем статус воркера
echo "⚙️  Статус воркера очередей:"
if docker exec news_ai_app ps aux | grep -q "messenger:consume"; then
    echo "✅ Воркер запущен"
else
    echo "❌ Воркер не запущен"
fi
echo

# Проверяем логи парсинга
echo "📋 Последние записи в логах парсинга:"
if [ -f /tmp/news_parsing.log ]; then
    tail -5 /tmp/news_parsing.log
else
    echo "Лог файл не найден"
fi
echo

# Проверяем cron задачи
echo "⏰ Cron задачи:"
crontab -l | grep news_ai || echo "Cron задачи не найдены"
echo

echo "=== Конец мониторинга ==="
