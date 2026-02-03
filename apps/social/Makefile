setup:
	@echo "Setting up project..."
	cp -n .env.example .env || true
	docker compose up -d --build
	@echo "Waiting for containers to start..."
	sleep 10
	docker compose exec app composer install
	docker compose exec app php bin/console.php migrate

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

build:
	docker compose up -d --build

install:
	docker compose exec app composer install

migrate:
	docker compose exec app php bin/console.php migrate

logs:
	docker compose logs -f

shell:
	docker compose exec app bash

db-shell:
	docker compose exec db mysql -u root -p"$$(grep DB_ROOT_PASSWORD .env | cut -d '=' -f2)" "$$(grep DB_DATABASE .env | cut -d '=' -f2)"
