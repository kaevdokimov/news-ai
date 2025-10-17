# 📰 News AI - Система сбора новостей

[![Symfony](https://img.shields.io/badge/Symfony-7.3-blue.svg)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.4-green.svg)](https://php.net/)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)](https://docker.com/)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Современная система для автоматического сбора, обработки и управления новостями из RSS лент с веб-интерфейсом администратора.

## ✨ Возможности

- 📰 **Автоматический парсинг RSS лент** - поддержка множества форматов RSS/Atom
- 🔄 **Асинхронная обработка** - использование Symfony Messenger и RabbitMQ
- 🎛️ **Веб-админка** - удобное управление через Sonata Admin Bundle
- ⏰ **Автоматический запуск** - cron для парсинга каждые 5 минут
- 🗄️ **Надежное хранение** - PostgreSQL с оптимизированными индексами
- 🚀 **Docker Ready** - готовые контейнеры для быстрого развертывания
- 📊 **Мониторинг** - встроенные инструменты для отслеживания работы
- 🔍 **Поиск и фильтрация** - удобный поиск по новостям и источникам
- 🛡️ **Безопасность** - валидация данных и защита от дублирования

## 🚀 Быстрый старт

### Требования

- **Docker** 20.10+ и **Docker Compose** 2.0+
- **Git** для клонирования репозитория
- **8GB RAM** (рекомендуется для комфортной работы)

### Установка за 3 шага

1. **Клонируйте репозиторий:**
```bash
git clone <repository-url>
cd news-ai
```

2. **Запустите систему одной командой:**
```bash
./scripts/manage.sh install
```

3. **Настройте автоматический парсинг:**
```bash
./scripts/manage.sh cron
```

### 🎯 Готово! Система запущена

- **Админ-панель:** http://localhost:81/admin
- **Источники новостей:** http://localhost:81/admin/sources
- **Просмотр новостей:** http://localhost:81/admin/news
- **RabbitMQ Management:** http://localhost:15673 (guest/guest)
- **Статус системы:** `./scripts/manage.sh status`

## 📖 Использование

### 🎛️ Веб-интерфейс

**Админ-панель:** http://localhost:81/admin

#### Предустановленные источники новостей:
- 📰 **Лента.ру** - https://lenta.ru/rss/google-newsstand/main/
- 📰 **РИА Новости** - https://ria.ru/export/rss2/index.xml?page_type=google_newsstand
- 📰 **РБК** - https://rssexport.rbc.ru/rbcnews/news/30/full.rss
- 📰 **ТАСС** - https://tass.ru/rss/v2.xml
- 📰 **Правительство РФ** - http://government.ru/all/rss/

### 🛠️ Управление системой

Используйте удобный скрипт `manage.sh`:

```bash
# Основные команды
./scripts/manage.sh start     # Запустить систему
./scripts/manage.sh stop      # Остановить систему
./scripts/manage.sh restart   # Перезапустить систему
./scripts/manage.sh status    # Проверить статус

# Работа с новостями
./scripts/manage.sh parse     # Запустить парсинг
./scripts/manage.sh worker    # Запустить воркер очередей

# Настройка
./scripts/manage.sh install   # Установить зависимости
./scripts/manage.sh cron      # Настроить автоматический парсинг

# Мониторинг
./scripts/manage.sh logs      # Показать логи
./scripts/manage.sh status    # Полная статистика
```

### ⚡ Консольные команды

#### Парсинг новостей
```bash
# Парсинг всех активных источников (синхронно)
docker exec news_ai_app php bin/console app:parse-rss

# Парсинг конкретного источника
docker exec news_ai_app php bin/console app:parse-rss --source-id=1

# Асинхронный парсинг (рекомендуется)
docker exec news_ai_app php bin/console app:parse-rss --async
```

#### Управление очередями
```bash
# Запуск воркера очередей
docker exec news_ai_app php bin/console messenger:consume async

# Запуск с ограничениями
docker exec news_ai_app php bin/console messenger:consume async --limit=10 --memory-limit=128M
```

### ⏰ Автоматический парсинг

#### Настройка cron (рекомендуется)
```bash
# Настроить автоматический парсинг каждые 5 минут
./scripts/manage.sh cron
```

#### Production окружение
Для production используйте `DockerfileProduction` с встроенным cron:
```bash
# Сборка production образа
docker build -f .docker/app/DockerfileProduction -t news-ai:production .
```

## 🏗️ Архитектура проекта

```
news-ai/
├── 📁 src/
│   ├── 📁 Entity/              # Сущности базы данных
│   │   ├── NewsSource.php      # Источники новостей
│   │   └── NewsItem.php        # Новости
│   ├── 📁 Repository/          # Репозитории для работы с БД
│   ├── 📁 Service/             # Бизнес-логика
│   │   └── RssParserService.php # Парсер RSS лент
│   ├── 📁 Command/             # Консольные команды
│   │   └── ParseRssCommand.php # Команда парсинга
│   ├── 📁 Message/             # Сообщения для очередей
│   ├── 📁 MessageHandler/      # Обработчики сообщений
│   └── 📁 Admin/               # Админ-панели Sonata
├── 📁 scripts/                 # Скрипты управления
│   ├── manage.sh               # Основной скрипт управления
│   ├── monitor.sh              # Мониторинг системы
│   └── parse_news.sh           # Скрипт парсинга для cron
├── 📁 .docker/                 # Docker конфигурации
├── 📁 config/                  # Конфигурация Symfony
└── 📁 templates/               # Шаблоны Twig
```

## ⚙️ Конфигурация

### 🗄️ База данных
- **PostgreSQL:** `localhost:5434`
- **База:** `postgres`
- **Пользователь:** `postgres`
- **Пароль:** `postgres`

### 🐰 Очереди сообщений
- **RabbitMQ:** `localhost:5673`
- **Веб-интерфейс:** http://localhost:15673
- **Логин/Пароль:** `guest/guest`

### 🚀 Кэш
- **Redis:** `localhost:6380`

### 🌐 Веб-сервер
- **Nginx:** `localhost:81`
- **PHP-FPM:** Внутри контейнера

## 🔧 Разработка

### ➕ Добавление нового источника новостей

1. Откройте админ-панель: http://localhost:81/admin
2. Перейдите в раздел **"Источники новостей"**
3. Нажмите кнопку **"Создать"**
4. Заполните поля:
   - **Название источника** (например: "BBC News")
   - **URL RSS ленты** (например: "https://feeds.bbci.co.uk/news/rss.xml")
   - **Описание** (опционально)
   - **Активен** ✅ (галочка для включения парсинга)

### 🛠️ Настройка автоматического парсинга

#### Для разработки:
```bash
./scripts/manage.sh cron
```

#### Для production:
1. Используйте `DockerfileProduction`
2. Cron задача: `*/5 * * * *  php /var/www/app/bin/console app:parse-rss --async`
3. Логи парсинга: `/var/log/news_parsing.log`

## 📊 Мониторинг

### 📋 Логи системы
- **Логи приложения:** `var/log/dev.log`
- **Логи парсинга:** `/tmp/news_parsing.log` (на хосте)
- **Логи воркера:** `/tmp/worker.log` (на хосте)

### 📈 Статистика
- **Количество источников:** админ-панель → Источники новостей
- **Количество новостей:** админ-панель → Новости
- **Статус очередей:** RabbitMQ Management UI (http://localhost:15673)

### 🔍 Мониторинг в реальном времени
```bash
# Полная статистика системы
./scripts/manage.sh status

# Просмотр логов в реальном времени
./scripts/manage.sh logs

# Проверка статуса контейнеров
docker ps | grep news_ai
```

## 🚨 Устранение неполадок

### ❌ Проблемы с парсингом
1. **Проверьте доступность RSS ленты:**
   ```bash
   curl -I "URL_RSS_ЛЕНТЫ"
   ```
2. **Убедитесь, что источник активен** в админ-панели
3. **Проверьте логи:**
   ```bash
   docker exec news_ai_app tail -f var/log/dev.log
   ```

### ❌ Проблемы с очередями
1. **Проверьте статус RabbitMQ:**
   ```bash
   docker ps | grep rabbitmq
   ```
2. **Перезапустите воркер:**
   ```bash
   ./scripts/manage.sh worker
   ```
3. **Проверьте очереди:** http://localhost:15673

### ❌ Проблемы с базой данных
1. **Проверьте подключение к PostgreSQL:**
   ```bash
   docker exec news_ai_app php bin/console doctrine:query:sql "SELECT 1"
   ```
2. **Выполните миграции:**
   ```bash
   docker exec news_ai_app php bin/console doctrine:migrations:migrate
   ```

### 🔧 Общие команды диагностики
```bash
# Полная диагностика системы
./scripts/manage.sh status

# Перезапуск всей системы
./scripts/manage.sh restart

# Просмотр логов всех сервисов
docker compose logs -f
```

## 📄 Лицензия

MIT License - см. файл [LICENSE](LICENSE)

## 🤝 Поддержка

Если у вас возникли вопросы или проблемы:

1. Проверьте раздел "Устранение неполадок"
2. Изучите логи системы
3. Создайте issue в репозитории

---

**Создано с ❤️ для автоматизации сбора новостей**
