SHELL := /bin/sh
COMPOSE := docker compose

.DEFAULT_GOAL := help

.PHONY: help up down build backend-build frontend-build restart logs ps backend frontend artisan composer npm test frontend-test pint tsc oxlint

help: ## Mostra i target disponibili
	@echo "Target disponibili:"
	@echo "  up            Avvia i container (build se necessario)"
	@echo "  build         Ricostruisce le immagini"
	@echo "  backend-build Ricostruisce l'immagine base backend (solo dopo modifiche al Dockerfile: il codice è su bind mount con hot-reload)"
	@echo "  frontend-build Ricostruisce l'immagine base frontend (solo dopo modifiche al Dockerfile: il codice è su bind mount con hot-reload)"
	@echo "  down          Ferma i container"
	@echo "  logs       Log di tutti i container (follow)"
	@echo "  ps         Stato dei container"
	@echo "  backend    Shell bash nel container backend"
	@echo "  frontend   Shell sh nel container frontend"
	@echo "  artisan    es. make artisan cmd=about"
	@echo "  composer   es. make composer cmd=require laravel/sanctum"
	@echo "  npm        es. make npm cmd='run build'"
	@echo "  test       Esegue i test PHP (php artisan test)"
	@echo "  frontend-test Esegue i test frontend (Vitest + Testing Library)"
	@echo "  pint       Esegue Pint sul backend"
	@echo "  tsc        Type-check del frontend"
	@echo "  oxlint     Lint del frontend"

up:
	$(COMPOSE) up -d

build:
	$(COMPOSE) build

backend-build:
	$(COMPOSE) build backend && $(COMPOSE) up -d backend

frontend-build:
	$(COMPOSE) build frontend && $(COMPOSE) up -d frontend

down:
	$(COMPOSE) down

restart:
	$(COMPOSE) restart

logs:
	$(COMPOSE) logs -f

ps:
	$(COMPOSE) ps

backend:
	$(COMPOSE) exec backend bash

frontend:
	$(COMPOSE) exec frontend sh

artisan:
	$(COMPOSE) exec backend php artisan $(cmd)

composer:
	$(COMPOSE) exec backend composer $(cmd)

npm:
	$(COMPOSE) exec frontend npm $(cmd)

test:
	$(COMPOSE) exec backend php artisan test

frontend-test:
	$(COMPOSE) exec frontend npm test

pint:
	$(COMPOSE) exec backend ./vendor/bin/pint

tsc:
	$(COMPOSE) exec frontend npx tsc --noEmit

oxlint:
	$(COMPOSE) exec frontend npm run lint
