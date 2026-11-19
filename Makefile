EXEC = docker compose exec app
PHP = $(EXEC) php
COMPOSER = $(EXEC) composer

.PHONY: test composer bash up down

# Запуск всех тестов
test:
	$(EXEC) ./vendor/bin/phpunit $(if $(filter),--filter $(filter),)

test-dox:
	$(EXEC) ./vendor/bin/phpunit --testdox $(if $(filter),--filter $(filter),)

# Обновление автозагрузчика (или установка пакетов через make composer cmd="require ...")
composer:
	$(COMPOSER) $(or $(cmd),dump-autoload)

# Вход в консоль контейнера app
bash:
	$(EXEC) bash

# Управление контейнерами
up:
	docker compose up -d

down:
	docker compose down
