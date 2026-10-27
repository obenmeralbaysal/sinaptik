SHELL := /bin/bash
DC := docker compose --env-file .env
BACK := $(DC) exec backend
PHP ?= php

.DEFAULT_GOAL := help
.PHONY: help up up-sso down restart ps logs migrate seed test shell-backend

help: ## Komut listesi
	@awk 'BEGIN {FS = ":.*##"; printf "Targets:\n"} /^[a-zA-Z_-]+:.*##/ {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

up: ## Konteynerleri başlat (127.0.0.1)
	$(DC) up -d --build

up-sso: ## Yerel Zitadel (profile sso, 127.0.0.1)
	$(DC) --profile sso up -d zitadel

down: ## Konteynerleri durdur
	$(DC) down

restart: ## Yeniden başlat
	$(DC) restart

ps: ## Durum
	$(DC) ps

logs: ## Günlükler
	$(DC) logs -f --tail=200

migrate: ## Migrasyon
	$(BACK) php artisan migrate --force

seed: ## Plan kataloğunu yaz
	$(BACK) php artisan db:seed --force

test: ## PHPUnit (sqlite, Docker gerekmez)
	cd backend && $(PHP) artisan test

shell-backend: ## Backend kabuğu
	$(BACK) bash
