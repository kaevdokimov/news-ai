# Пути к исходным и целевым файлам
RSA_PUB_PATH := $(HOME)/.ssh/id_rsa.pub
RSA_PUB_DESTINATION := ./id_rsa.pub
RSA_PATH := $(HOME)/.ssh/id_rsa
RSA_DESTINATION := ./id_rsa

# Цвета для форматирования
YELLOW := \033[1;33m
GREEN  := \033[1;32m
RED    := \033[1;31m
BLUE   := \033[1;34m
NC     := \033[0m # Без цвета

.PHONY: init up down restart docker-up docker-down docker-down-clear docker-pull docker-build \
        app-init jwt-ssl db-create db-create-test migrations-up db-fixtures encryption-key \
        console copy-key clean lint cs-check cs-fixer rector phpunit phpunit-unit phpunit-application phpunit-functional clear-cache \
        composer-update composer-install messenger-setup messenger-debug messenger-consume \
        messenger-stats messenger-stop help \
        opensearch-create-product-index opensearch-create-search-index opensearch-delete-indexes \
        opensearch-update-brand-index opensearch-update-category-index opensearch-update-product-index \
        opensearch-update-seller-index

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
init:		   docker-down-clear docker-pull copy-key docker-build up app-init clean
	@echo "${GREEN}Проект успешно инициализирован${NC}"

# Запуск контейнеров
up:			 docker-up
	@echo "${GREEN}Контейнеры запущены${NC}"

# Остановка контейнеров
down:		   docker-down
	@echo "${GREEN}Контейнеры остановлены${NC}"

# Перезапуск контейнеров
restart:		down up
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
app-init:	   composer-install db-create migrations-up db-fixtures encryption-key jwt-ssl
	@echo "${GREEN}Приложение успешно инициализировано${NC}"

# Генерация JWT ключей
jwt-ssl:
	@echo "${BLUE}Генерация JWT ключей...${NC}"
	docker compose run --rm es_back_app php bin/console lexik:jwt:generate-keypair --skip-if-exists
	@echo "${GREEN}JWT ключи созданы${NC}"

# Создание базы данных
db-create:
	@echo "${BLUE}Создание базы данных...${NC}"
	docker compose run --rm es_back_app php bin/console doctrine:database:create --if-not-exists
	@echo "${GREEN}База данных создана${NC}"

# Создание тестовой базы данных
db-create-test:
	@echo "${BLUE}Создание тестовой базы данных...${NC}"
	docker compose run --rm es_back_app php bin/console doctrine:database:create --env=test --if-not-exists
	@echo "${GREEN}Тестовая база данных создана${NC}"

# Применение миграций
migrations-up:
	@echo "${BLUE}Применение миграций...${NC}"
	docker compose run --rm es_back_app php bin/console doctrine:migrations:migrate -n
	@echo "${GREEN}Миграции успешно применены${NC}"

# Загрузка фикстур
db-fixtures:
	@echo "${BLUE}Загрузка фикстур...${NC}"
	docker compose run --rm es_back_app php bin/console doctrine:fixtures:load --group=dev -n --append
	@echo "${GREEN}Фикстуры загружены${NC}"

# Генерация ключа шифрования
encryption-key:
	@echo "${BLUE}Генерация ключа шифрования...${NC}"
	docker compose run --rm es_back_app php bin/console app:encryption:generate-key
	@echo "${GREEN}Ключ шифрования сгенерирован${NC}"

# Запуск консоли в контейнере
console:
	@echo "${BLUE}Запуск консоли в контейнере...${NC}"
	docker exec -it es_back_app bash

# Копирование SSH ключей
copy-key:
	@echo "${BLUE}Копирование файла${NC}${YELLOW}$(RSA_PUB_PATH)${NC}${BLUE}в${NC}${YELLOW}$(RSA_PUB_DESTINATION)${NC}"
	@cp $(RSA_PUB_PATH) $(RSA_PUB_DESTINATION)
	@echo "${BLUE}Копирование файла${NC}${YELLOW}$(RSA_PATH)${NC}${BLUE}в${NC}${YELLOW}$(RSA_DESTINATION)${NC}"
	@cp $(RSA_PATH) $(RSA_DESTINATION)
	@echo "${GREEN}Копирование завершено${NC}"

# Очистка временных файлов
clean:
	@echo "${BLUE}Удаление файла${NC}${YELLOW}$(RSA_PUB_DESTINATION)${NC}"
	@rm -f $(RSA_PUB_DESTINATION)
	@echo "${BLUE}Удаление файла${NC}${YELLOW}$(RSA_DESTINATION)${NC}"
	@rm -f $(RSA_DESTINATION)
	@echo "${GREEN}Очистка завершена${NC}"

############################
# Команды для проверки и исправления качества кода
############################
# Проверка синтаксиса
lint:
	@echo "${BLUE}Проверка синтаксиса...${NC}"
	docker compose run --rm es_back_app sudo composer lint
	@echo "${GREEN}Проверка синтаксиса завершена${NC}"

# Проверка стиля кода
cs-check:
	@echo "${BLUE}Проверка стиля кода...${NC}"
	docker compose run --rm es_back_app sudo composer php-cs-check
	@echo "${GREEN}Проверка стиля кода завершена${NC}"

# Исправление стиля кода
cs-fixer:
	@echo "${BLUE}Исправление стиля кода...${NC}"
	docker compose run --rm es_back_app sudo composer php-cs-fixer
	@echo "${GREEN}Стиль кода исправлен${NC}"

# Исправление кода, авторефакторинг
rector:
	@echo "${BLUE}Исправление кода, авторефакторинг...${NC}"
	docker compose run --rm es_back_app sudo composer rector
	@echo "${GREEN}Код исправлен, авторефакторинг завершен${NC}"

############################
# Команды для тестов
############################

# Запуск Unit-тестов
test-unit:
	@echo "${BLUE}Запуск Unit-тестов...${NC}"
	docker compose run --rm es_back_app sudo XDEBUG_MODE=coverage composer test-unit
	@echo "${GREEN}Unit-тесты выполнены${NC}"

# Запуск Application-тестов
test-application:
	@echo "${BLUE}Запуск Application-тестов...${NC}"
	docker compose run --rm es_back_app sudo XDEBUG_MODE=coverage composer test-application
	@echo "${GREEN}Application-тесты выполнены${NC}"

# Запуск Integration-тестов
test-integration:
	@echo "${BLUE}Запуск Functional-тестов...${NC}"
	docker compose run --rm es_back_app sudo XDEBUG_MODE=coverage composer test-integration
	@echo "${GREEN}Functional-тесты выполнены${NC}"

# Запуск всех тестов
test:
	@echo "${BLUE}Запуск всех тестов...${NC}"
	docker compose run --rm es_back_app sudo XDEBUG_MODE=coverage composer test
	@echo "${GREEN}Все тесты выполнены${NC}"

############################
# Команды для управления зависимостями через Composer
############################
# Обновление зависимостей
composer-update:
	@echo "${BLUE}Обновление зависимостей...${NC}"
	docker compose run --rm es_back_app sudo composer update -W -o
	@echo "${GREEN}Зависимости обновлены${NC}"

# Установка зависимостей
composer-install:
	@echo "${BLUE}Установка зависимостей...${NC}"
	docker compose run --rm es_back_app sudo composer install -o
	@echo "${GREEN}Зависимости установлены${NC}"

############################
# Команды для работы с очередью сообщений
############################

# Настройка транспорта сообщений
messenger-setup:
	@echo "${BLUE}Настройка транспорта сообщений...${NC}"
	docker-compose run --rm es_back_app php bin/console messenger:setup-transports --profile
	@echo "${GREEN}Транспорт сообщений настроен${NC}"

# Отладка обработки сообщений
messenger-debug:
	@echo "${BLUE}Запуск отладки обработки сообщений...${NC}"
	docker-compose run --rm es_back_app php bin/console debug:messenger --profile
	@echo "${GREEN}Отладка завершена${NC}"

# Запуск обработки сообщений
messenger-consume:
	@echo "${BLUE}Запуск обработки сообщений...${NC}"
	docker-compose run --rm es_back_app php -d memory_limit=450M bin/console messenger:consume es_high_priority es_billing es_notifications es_collection_planner_response es_low_priority failed -vvv --profile
	@echo "${GREEN}Обработка сообщений запущена${NC}"

# Просмотр статистики очередей
messenger-stats:
	@echo "${BLUE}Получение статистики очередей...${NC}"
	docker-compose run --rm es_back_app php bin/console messenger:stats --profile
	@echo "${GREEN}Статистика получена${NC}"

# Остановка обработчиков
messenger-stop:
	@echo "${BLUE}Остановка обработчиков сообщений...${NC}"
	docker-compose run --rm es_back_app php bin/console messenger:stop-workers --profile
	@echo "${GREEN}Обработчики остановлены${NC}"

############################
# Команды для работы с OpenSearch
############################

# Обеспечить существование поискового индекса Товары
opensearch-create-product-index:
	@echo "${BLUE}Создание поискового индекса Товары...${NC}"
	docker-compose run --rm es_back_app php bin/console app:opensearch:create-product-index
	@echo "${GREEN}Поисковый индекс Товары создан${NC}"

# Обеспечить существование поискового индекса Поисковые запросы
opensearch-create-search-index:
	@echo "${BLUE}Создание поискового индекса Поисковые запросы...${NC}"
	docker-compose run --rm es_back_app php bin/console app:opensearch:create-search-index
	@echo "${GREEN}Поисковый индекс Поисковые запросы создан${NC}"

# Обновить поисковый индекс Бренды
opensearch-update-brand-index:
	@echo "${BLUE}Обновление поискового индекса Бренды...${NC}"
	docker-compose run --rm es_back_app php bin/console app:opensearch:update-brand-index
	@echo "${GREEN}Поисковый индекс Бренды обновлен${NC}"

# Обновить поисковый индекс Категории
opensearch-update-category-index:
	@echo "${BLUE}Обновление поискового индекса Категории...${NC}"
	docker-compose run --rm es_back_app php bin/console app:opensearch:update-category-index
	@echo "${GREEN}Поисковый индекс Категории обновлен${NC}"

# Обновить поисковый индекс Товары
opensearch-update-product-index:
	@echo "${BLUE}Обновление поискового индекса Товары...${NC}"
	docker-compose run --rm es_back_app php bin/console app:opensearch:update-product-index
	@echo "${GREEN}Поисковый индекс Товары обновлен${NC}"

# Обновить поисковый индекс Селлеры
opensearch-update-seller-index:
	@echo "${BLUE}Обновление поискового индекса Селлеры...${NC}"
	docker-compose run --rm es_back_app php bin/console app:opensearch:update-seller-index
	@echo "${GREEN}Поисковый индекс Селлеры обновлен${NC}"

# Удалить все поисковые индексы
opensearch-delete-indexes:
	@echo "${BLUE}Удаление всех поисковых индексов...${NC}"
	docker-compose run --rm es_back_app php bin/console app:opensearch:delete-indexes
	@echo "${GREEN}Все поисковые индексы удалены${NC}"
