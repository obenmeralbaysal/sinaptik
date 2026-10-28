# Sinaptik Social

Çok kiracılı sosyal medya ve reklam SaaS'ı. Faz 1 temeli: Laravel 13 JSON API + Filament süper admin, Next.js arayüz, PostgreSQL 16 (RLS), Redis ve Horizon.

Alan adı: `social.sinaptik.com.tr`. Eski kod `legacy/pre-saas-2026-10-17` dalında durur. Bu dal onu değiştirmez ve hiçbir yere deploy etmez.

## Yerel çalıştırma

```bash
cp .env.example .env
# NPM ağı .env içindeki NPM_NETWORK. Varsayılan nginx-proxy-manager_default.
# Test sunucusu dış ağı root_default kullanır. deploy.sh ağ yoksa oluşturur.
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
- [Sinapp entegrasyonu](docs/SINAPP.md)
- [Meta kurulumu](docs/META_SETUP.md)
- [MCP](docs/MCP.md)
- [Deploy](deploy/README.md)
- [Marka](docs/BRAND.md)

---

## English

Sinaptik Social is a multi-tenant social and ads SaaS foundation. Laravel 13 API, Filament super-admin, Next.js, PostgreSQL row-level security, Redis, Horizon. The product name is Sinaptik Social; the domain is social.sinaptik.com.tr. Do not deploy from this repository automatically. Copy `.env.example`, then run `./deploy/deploy.sh`. The external Docker network name is `NPM_NETWORK` (default `nginx-proxy-manager_default`; the test server uses `root_default`). Fake billing and a fake Meta driver are the local defaults. Secrets stay out of git. Brand tokens and the placeholder logo live in [docs/BRAND.md](docs/BRAND.md).
