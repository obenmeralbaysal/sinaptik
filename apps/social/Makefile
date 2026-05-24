.PHONY: setup up down api web queue sched migrate fresh test logs

setup:
	@echo "==> Starting infra (postgres + redis + mailhog)"
	docker compose up -d
	@echo "==> Installing backend deps"
	cd backend && composer install
	@echo "==> Installing frontend deps"
	cd frontend && npm install
	@echo "==> Running migrations"
	cd backend && php artisan migrate --seed
	@echo "Done. Run 'make api' and 'make web' in separate terminals."

up:
	docker compose up -d

down:
	docker compose down

api:
	cd backend && php artisan serve --host=0.0.0.0 --port=8000

web:
	cd frontend && npm run dev

queue:
	cd backend && php artisan horizon

sched:
	cd backend && php artisan schedule:work

migrate:
	cd backend && php artisan migrate

fresh:
	cd backend && php artisan migrate:fresh --seed

test:
	cd backend && php artisan test

logs:
	docker compose logs -f
