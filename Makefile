# Цвета для форматирования
YELLOW := \033[1;33m
GREEN  := \033[1;32m
RED    := \033[1;31m
BLUE   := \033[1;34m
NC     := \033[0m # Без цвета

.PHONY: init up down restart docker-up docker-down docker-down-clear docker-pull docker-build \
        app-init jwt-ssl db-create db-create-test migrations-up db-fixtures encryption-key \
        console copy-key clean lint cs-check cs-fixer rector \
        composer-update composer-install messenger-setup messenger-debug messenger-consume \
        messenger-stats messenger-stop help

# Показать список доступных команд
help:
	@echo "${YELLOW}Доступные команды:${NC}"
	@echo ""
	@awk '/^[a-zA-Z0-9_-]+:/ { \
		helpMessage = match(lastLine, /^# (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")-1); \
			helpMessage = substr(lastLine, RSTART + 2, RLENGTH); \
			printf "  ${GREEN}%-20s${NC} %s\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)
	@echo ""
	@echo "${BLUE}Используйте ${RED}make <команда>${BLUE} для выполнения операции${NC}"

############################
# Основные команды
############################
# Полная инициализация проекта
init: docker-down-clear docker-pull docker-build up app-init clean
	@echo "${GREEN}Проект успешно инициализирован${NC}"

# Запуск контейнеров
up: docker-up
	@echo "${GREEN}Контейнеры запущены${NC}"

# Остановка контейнеров
down: docker-down
	@echo "${GREEN}Контейнеры остановлены${NC}"

# Перезапуск контейнеров
restart: down up
	@echo "${GREEN}Контейнеры перезапущены${NC}"

############################
# Команды для управления Docker контейнерами
############################
# Запуск контейнеров в фоновом режиме
docker-up:
	@echo "${BLUE}Запуск контейнеров...${NC}"
	docker compose up -d
	@echo "${GREEN}Контейнеры успешно запущены${NC}"

# Остановка контейнеров
docker-down:
	@echo "${BLUE}Остановка контейнеров...${NC}"
	docker compose down
	@echo "${GREEN}Контейнеры успешно остановлены${NC}"

# Остановка контейнеров с удалением томов
docker-down-clear:
	@echo "${BLUE}Остановка контейнеров и удаление томов...${NC}"
	docker compose down -v --remove-orphans
	@echo "${GREEN}Контейнеры остановлены и тома удалены${NC}"

# Загрузка образов контейнеров
docker-pull:
	@echo "${BLUE}Загрузка образов контейнеров...${NC}"
	docker compose pull
	@echo "${GREEN}Образы успешно загружены${NC}"

# Сборка образов контейнеров
docker-build:
	@echo "${BLUE}Сборка образов контейнеров...${NC}"
	docker compose build --pull
	@echo "${GREEN}Образы успешно собраны${NC}"

############################
# Команды для начальной настройки приложения
############################
# Инициализация приложения
app-init: composer-install db-create migrations-up db-fixtures encryption-key jwt-ssl
	@echo "${GREEN}Приложение успешно инициализировано${NC}"

# Генерация JWT ключей
jwt-ssl:
	@echo "${BLUE}Генерация JWT ключей...${NC}"
	docker compose run --rm news_ai_app php bin/console lexik:jwt:generate-keypair --skip-if-exists
	@echo "${GREEN}JWT ключи созданы${NC}"

# Создание базы данных
db-create:
	@echo "${BLUE}Создание базы данных...${NC}"
	docker compose run --rm news_ai_app php bin/console doctrine:database:create --if-not-exists
	@echo "${GREEN}База данных создана${NC}"

# Создание тестовой базы данных
db-create-test:
	@echo "${BLUE}Создание тестовой базы данных...${NC}"
	docker compose run --rm news_ai_app php bin/console doctrine:database:create --env=test --if-not-exists
	@echo "${GREEN}Тестовая база данных создана${NC}"

# Применение миграций
migrations-up:
	@echo "${BLUE}Применение миграций...${NC}"
	docker compose run --rm news_ai_app php bin/console doctrine:migrations:migrate -n --allow-no-migration
	@echo "${GREEN}Миграции успешно применены${NC}"

# Загрузка фикстур
db-fixtures:
	@echo "${BLUE}Загрузка фикстур...${NC}"
	docker compose run --rm news_ai_app php bin/console doctrine:fixtures:load --group=dev -n --append
	@echo "${GREEN}Фикстуры загружены${NC}"

# Генерация ключа шифрования
encryption-key:
	@echo "${BLUE}Генерация ключа шифрования...${NC}"
	docker compose run --rm news_ai_app php bin/console app:encryption:generate-key
	@echo "${GREEN}Ключ шифрования сгенерирован${NC}"

# Запуск консоли в контейнере
console:
	@echo "${BLUE}Запуск консоли в контейнере...${NC}"
	docker exec -it news_ai_app bash

############################
# Команды для управления зависимостями через Composer
############################
# Обновление зависимостей
composer-update:
	@echo "${BLUE}Обновление зависимостей...${NC}"
	docker compose run --rm news_ai_app composer update -W -o
	@echo "${GREEN}Зависимости обновлены${NC}"

# Установка зависимостей
composer-install:
	@echo "${BLUE}Установка зависимостей...${NC}"
	docker compose run --rm news_ai_app composer install -o
	@echo "${GREEN}Зависимости установлены${NC}"

############################
# Команды для работы с очередью сообщений
############################

# Настройка транспорта сообщений
messenger-setup:
	@echo "${BLUE}Настройка транспорта сообщений...${NC}"
	docker-compose run --rm news_ai_app php bin/console messenger:setup-transports --profile
	@echo "${GREEN}Транспорт сообщений настроен${NC}"

# Отладка обработки сообщений
messenger-debug:
	@echo "${BLUE}Запуск отладки обработки сообщений...${NC}"
	docker-compose run --rm news_ai_app php bin/console debug:messenger --profile
	@echo "${GREEN}Отладка завершена${NC}"

# Запуск обработки сообщений
messenger-consume:
	@echo "${BLUE}Запуск обработки сообщений...${NC}"
	docker-compose run --rm news_ai_app php -d memory_limit=450M bin/console messenger:consume news_ai_high_priority news_ai_low_priority failed -vvv --profile
	@echo "${GREEN}Обработка сообщений запущена${NC}"

# Просмотр статистики очередей
messenger-stats:
	@echo "${BLUE}Получение статистики очередей...${NC}"
	docker-compose run --rm news_ai_app php bin/console messenger:stats --profile
	@echo "${GREEN}Статистика получена${NC}"

# Остановка обработчиков
messenger-stop:
	@echo "${BLUE}Остановка обработчиков сообщений...${NC}"
	docker-compose run --rm news_ai_app php bin/console messenger:stop-workers --profile
	@echo "${GREEN}Обработчики остановлены${NC}"
