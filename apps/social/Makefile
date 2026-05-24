###############################################################################
# Sinapp Social — Makefile
#
# Production (docker):  make deploy        # build, migrate, seed, cache, up
# Update on server:     make update        # git pull + rebuild + migrate
# Local dev (host):     make api / make web / make queue / make sched
###############################################################################

SHELL := /bin/bash
DC    := docker compose --env-file .env
BACK  := $(DC) exec backend
BACK_RUN := $(DC) run --rm backend

.DEFAULT_GOAL := help

.PHONY: help \
        deploy setup update up down restart rebuild ps logs \
        build build-backend build-frontend \
        migrate fresh seed key cache cache-clear storage-link tinker \
        shell-backend shell-frontend shell-horizon \
        horizon-status pg-shell redis-cli \
        api web queue sched test

help:  ## Show this help
	@awk 'BEGIN {FS = ":.*##"; printf "Targets:\n"} /^[a-zA-Z_-]+:.*##/ {printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# --------------------------------------------------------------------------
# Production / docker
# --------------------------------------------------------------------------

deploy:  ## Full deploy: build images, run migrations + seed, cache config, start everything
	@test -f .env || (echo "ERROR: .env not found. Run 'cp .env.example .env' and fill it in." && exit 1)
	@echo "==> Building backend image (base for horizon/scheduler)"
	$(DC) build backend
	@echo "==> Building horizon, scheduler, frontend"
	$(DC) build horizon scheduler frontend
	@echo "==> Starting infra (postgres + redis)"
	$(DC) up -d postgres redis
	@echo "==> Waiting for postgres to be healthy"
	@until $(DC) exec -T postgres pg_isready -U $$(grep ^DB_USERNAME .env | cut -d= -f2) >/dev/null 2>&1; do sleep 1; done
	@echo "==> Ensuring APP_KEY"
	@grep -q '^APP_KEY=base64:' .env || $(BACK_RUN) php artisan key:generate --show --no-interaction \
		| xargs -I{} sed -i.bak 's|^APP_KEY=.*|APP_KEY={}|' .env && rm -f .env.bak
	@echo "==> Running migrations + seeders"
	$(BACK_RUN) php artisan migrate --force --seed
	@echo "==> Starting all services"
	$(DC) up -d
	@echo "==> Caching config / routes / views"
	$(BACK) php artisan config:cache
	$(BACK) php artisan route:cache
	$(BACK) php artisan view:cache
	$(BACK) php artisan storage:link || true
	@echo "==> Done. Frontend: http://localhost:$$(grep ^HOST_PORT_FRONTEND .env | cut -d= -f2)   Backend: http://localhost:$$(grep ^HOST_PORT_BACKEND .env | cut -d= -f2)"

setup: deploy  ## Alias for first-time deploy

update:  ## git pull + rebuild + migrate (zero-touch upgrade on the server)
	git pull --ff-only
	$(DC) build backend
	$(DC) build horizon scheduler frontend
	$(DC) up -d
	$(BACK) php artisan migrate --force
	$(BACK) php artisan config:cache
	$(BACK) php artisan route:cache
	$(BACK) php artisan view:cache
	$(DC) restart backend horizon scheduler frontend

up:           ## Start containers
	$(DC) up -d

down:         ## Stop and remove containers
	$(DC) down

restart:      ## Restart all containers
	$(DC) restart

rebuild:      ## Rebuild images and restart (no migrate)
	$(DC) build backend
	$(DC) build horizon scheduler frontend
	$(DC) up -d

ps:           ## Show running services
	$(DC) ps

logs:         ## Tail logs (all services)
	$(DC) logs -f --tail=200

# --------------------------------------------------------------------------
# Targeted builds
# --------------------------------------------------------------------------

build: build-backend build-frontend  ## Build all images

build-backend:  ## Build backend (+horizon + scheduler share the image)
	$(DC) build backend horizon scheduler

build-frontend: ## Build frontend (Next.js standalone)
	$(DC) build frontend

# --------------------------------------------------------------------------
# Backend (artisan) helpers
# --------------------------------------------------------------------------

migrate:        ## Run pending migrations
	$(BACK) php artisan migrate --force

fresh:          ## Drop everything, re-migrate, re-seed (DESTRUCTIVE)
	$(BACK) php artisan migrate:fresh --force --seed

seed:           ## Run seeders
	$(BACK) php artisan db:seed --force

key:            ## Generate APP_KEY (writes to .env inside container only — copy manually)
	$(BACK_RUN) php artisan key:generate --show

cache:          ## Cache config / routes / views
	$(BACK) php artisan config:cache
	$(BACK) php artisan route:cache
	$(BACK) php artisan view:cache

cache-clear:    ## Clear all caches
	$(BACK) php artisan optimize:clear

storage-link:   ## php artisan storage:link
	$(BACK) php artisan storage:link

tinker:         ## Open tinker
	$(BACK) php artisan tinker

# --------------------------------------------------------------------------
# Shells
# --------------------------------------------------------------------------

shell-backend:  ## Bash inside backend container
	$(BACK) bash

shell-frontend: ## Shell inside frontend container
	$(DC) exec frontend sh

shell-horizon:  ## Bash inside horizon container
	$(DC) exec horizon bash

pg-shell:       ## psql shell
	$(DC) exec postgres psql -U $$(grep ^DB_USERNAME .env | cut -d= -f2) -d $$(grep ^DB_DATABASE .env | cut -d= -f2)

redis-cli:      ## redis-cli (auth from .env)
	$(DC) exec redis redis-cli -a $$(grep ^REDIS_PASSWORD .env | cut -d= -f2)

horizon-status: ## Horizon status
	$(BACK) php artisan horizon:status

# --------------------------------------------------------------------------
# Host-mode dev (no docker for app code; use only pg/redis/mailhog containers)
# --------------------------------------------------------------------------

api:   ## (dev) Laravel on :8000
	cd backend && php artisan serve --host=0.0.0.0 --port=8000

web:   ## (dev) Next.js on :3000
	cd frontend && npm run dev

queue: ## (dev) Horizon worker on host
	cd backend && php artisan horizon

sched: ## (dev) Scheduler tick on host
	cd backend && php artisan schedule:work

test:  ## Run backend tests
	$(BACK) php artisan test
