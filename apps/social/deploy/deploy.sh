#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo ".env .env.example dosyasından oluşturuldu. Üretim sırlarını yazın." >&2
fi

env_value() {
  local key="$1"
  local fallback="$2"
  local line
  line="$(grep -E "^${key}=" .env | tail -n 1 || true)"
  if [[ -z "$line" ]]; then
    printf '%s' "$fallback"
    return
  fi
  printf '%s' "${line#*=}"
}

NPM_NETWORK="$(env_value NPM_NETWORK nginx-proxy-manager_default)"
if ! docker network inspect "$NPM_NETWORK" >/dev/null 2>&1; then
  docker network create "$NPM_NETWORK"
fi

docker compose build
docker compose up -d db redis

PGUSER="$(env_value POSTGRES_USER sinsocial)"
APP_PASS="$(env_value DB_PASSWORD change-me-app)"

for _ in $(seq 1 60); do
  if docker compose exec -T db pg_isready -U "$PGUSER" >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

docker compose exec -T db psql -U "$PGUSER" -d postgres -v ON_ERROR_STOP=1 <<SQL
DO \$\$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'sinsocial_app') THEN
    CREATE ROLE sinsocial_app LOGIN PASSWORD '${APP_PASS}' NOSUPERUSER NOBYPASSRLS NOCREATEDB;
  ELSE
    ALTER ROLE sinsocial_app WITH LOGIN PASSWORD '${APP_PASS}' NOSUPERUSER NOBYPASSRLS NOCREATEDB;
  END IF;
END
\$\$;
SQL

if ! docker compose exec -T db psql -U "$PGUSER" -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='sinsocial'" | grep -q 1; then
  docker compose exec -T db psql -U "$PGUSER" -d postgres -c "CREATE DATABASE sinsocial OWNER sinsocial_app"
fi
docker compose exec -T db psql -U "$PGUSER" -d sinsocial -c "ALTER SCHEMA public OWNER TO sinsocial_app; GRANT ALL ON SCHEMA public TO sinsocial_app;"

if ! grep -q '^APP_KEY=base64:' .env; then
  KEY="$(docker compose run --rm --no-deps --entrypoint php backend artisan key:generate --show | tail -n 1)"
  sed -i "s|^APP_KEY=.*|APP_KEY=${KEY}|" .env
fi

docker compose up -d backend
docker compose exec -T backend php artisan migrate --force
docker compose exec -T backend php artisan db:seed --force
docker compose exec -T backend php artisan config:cache
docker compose exec -T backend php artisan route:cache
docker compose exec -T backend php artisan filament:optimize
docker compose up -d

PORT="$(env_value HOST_PORT_WEB 3021)"
for _ in $(seq 1 40); do
  if curl -fsS "http://127.0.0.1:${PORT}/api/health"; then
    printf '\n'
    exit 0
  fi
  sleep 2
done

echo "Sağlık kontrolü başarısız: http://127.0.0.1:${PORT}/api/health" >&2
exit 1
