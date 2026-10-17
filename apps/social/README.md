# Sinapp Social

Çok kiracılı sosyal medya ve reklam SaaS'ı. Faz 1 temeli: Laravel 13 JSON API + Filament süper admin, Next.js arayüz, PostgreSQL 16 (RLS), Redis ve Horizon.

Alan adı: `social.sinaptik.com.tr`. Eski kod `legacy/pre-saas-2026-10-17` dalında durur. Bu dal onu değiştirmez ve hiçbir yere deploy etmez.

## Yerel çalıştırma

```bash
cp .env.example .env
docker network create nginx-proxy-manager_default   # NPM ağı yoksa
./deploy/deploy.sh
```

Arayüz ve API aynı girişten gelir: `http://127.0.0.1:3021`. Kart verisi yoktur. Yerelde `BILLING_GATEWAY=fake` ve `META_DRIVER=fake` yeterlidir.

API'yi Docker olmadan denemek için `backend/.env.example` dosyasını `backend/.env` yapın, Postgres'te `sinsocial_app` rolünü oluşturun ve `php artisan migrate --seed` çalıştırın. Arayüz: `cd frontend && npm install && npm run dev`.

Süper admin:

```bash
docker compose exec backend php artisan sinsocial:admin
```

`ADMIN_EMAIL` ve `ADMIN_PASSWORD` `.env` içinde olmalıdır. Panel: `/admin`.

## Test

```bash
cd backend && php artisan test
cd frontend && npm run lint && npm run build
```

## Belgeler

- [Mimari](docs/ARCHITECTURE.md)
- [Faturalama adaptörü](docs/BILLING_ADAPTER.md)
- [Meta kurulumu](docs/META_SETUP.md)
- [MCP](docs/MCP.md)
- [Deploy](deploy/README.md)

---

## English

Multi-tenant social and ads SaaS foundation. Laravel 13 API, Filament super-admin, Next.js, PostgreSQL row-level security, Redis, Horizon. Do not deploy from this repository automatically. Copy `.env.example`, create the external Nginx Proxy Manager network if needed, then run `./deploy/deploy.sh`. Fake billing and a fake Meta driver are the local defaults. Secrets stay out of git.
