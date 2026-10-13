# Sinapp Social v2

Multi-tenant social media automation. Laravel 11 (headless API) + Next.js 15 (App Router), fully dockerized and deployed behind **nginx-proxy-manager**.

- **Production:** `social.sinaptik.com.tr` (frontend + API at `/api`)
- **Tenant URL pattern:** `/t/{slug}` (e.g. `/t/obenmeralbaysal`)
- **First integration:** X (Twitter) via OAuth 2.0 + PKCE (S256)

## Documentation

| Topic | English | Türkçe |
|---|---|---|
| Server deployment (NPM, single `.env`) | [DEPLOYMENT_EN](docs/deployment/DEPLOYMENT_EN.md) | [DEPLOYMENT_TR](docs/deployment/DEPLOYMENT_TR.md) |
| HTTP API reference | [API_EN](docs/api/API_EN.md) | [API_TR](docs/api/API_TR.md) |
| X (Twitter) developer setup | [x-twitter-EN](docs/providers/x-twitter-setup-EN.md) | [x-twitter-TR](docs/providers/x-twitter-setup-TR.md) |
| Instagram Graph API setup | [instagram-EN](docs/providers/instagram-setup-EN.md) | [instagram-TR](docs/providers/instagram-setup-TR.md) |
| Facebook Pages API setup | [facebook-EN](docs/providers/facebook-setup-EN.md) | [facebook-TR](docs/providers/facebook-setup-TR.md) |
| Roadmap | [NEXT_PHASES_EN](docs/NEXT_PHASES_EN.md) | [NEXT_PHASES_TR](docs/NEXT_PHASES_TR.md) |

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 11, Sanctum (SPA cookies), Horizon, spatie/laravel-permission |
| Frontend | Next.js 15 (standalone), Tailwind v4, TanStack Query, Axios, shadcn-style UI, next-themes |
| Database | PostgreSQL 16 |
| Cache / Queue / Session | Redis 7 |
| Mail (dev) | MailHog |
| Deploy | Docker Compose behind nginx-proxy-manager |

## Folder layout

```
sinapp-social/
├── backend/             # Laravel 11 API
├── frontend/            # Next.js 15 SPA (standalone build)
├── docker/              # Backend (php-fpm+nginx), horizon, scheduler, frontend Dockerfiles
├── docs/                # EN + TR documentation
├── docker-compose.yml
├── .env.example         # SINGLE source of truth (everything reads from here)
├── Makefile
└── README.md
```

## Production deploy in 3 commands

```bash
cp .env.example .env     # fill in domain + secrets
make deploy              # pull, build, migrate, seed, cache, verify
# then create one NPM Proxy Host (frontend :3011 + /api,/sanctum → :8011)
```

Full step-by-step + NPM screenshots-worth of detail: [docs/deployment/DEPLOYMENT_EN.md](docs/deployment/DEPLOYMENT_EN.md).

## Host port convention

Every host-exposed port ends in **`11`** so NPM forwards predictably:

| Service | Host port → Container |
|---|---|
| Frontend (Next.js) | `3011` → `3000` |
| Backend (Laravel) | `8011` → `80` |
| PostgreSQL | `5411` → `5432` |
| Redis | `6311` → `6379` |
| MailHog SMTP | `1011` → `1025` |
| MailHog UI | `8211` → `8025` |

Override via `HOST_PORT_*` variables in `.env`.

## Single `.env` philosophy

There is **one** `.env` at the repo root. Backend, frontend, and every container reads from it (via `env_file: .env` in `docker-compose.yml`). You never duplicate variables.

## Local dev (without Docker for the app code)

If you prefer running PHP and Node on the host while only using Docker for postgres/redis/mailhog:

```bash
make up                  # start pg + redis + mailhog only
make api                 # php artisan serve :8000
make web                 # next dev :3000
make queue               # horizon
make sched               # schedule:work
```

You'll also want `backend/.env` and `frontend/.env.local` for host dev — copy from `backend/.env.example` and `frontend/.env.example`. Next.js proxies `/api` and `/sanctum` to Laravel on `:8000`.

## Day-2 operations

```bash
make update              # git pull + rebuild + migrate
make logs                # tail every service
make ps                  # status
make shell-backend       # bash into Laravel container
make pg-shell            # psql
make horizon-status      # queue health
make fresh               # DESTRUCTIVE: drop DB + reseed
```

## How OAuth (X) works

1. User signs in to the SPA, lands on `/t/{slug}/accounts`.
2. Click *Connect X account* → SPA `POST /api/social-accounts/x/connect`.
3. Backend generates `code_verifier` (random 64 bytes), `code_challenge = base64url(sha256(verifier))`, signs a `state`, caches `{organization_id, verifier}` for 15 minutes, returns `auth_url`.
4. SPA redirects browser to `auth_url` on `x.com/i/oauth2/authorize`.
5. X redirects to `GET /api/social-accounts/x/callback?code=...&state=...` (no auth; state binds the org).
6. Backend exchanges the code, encrypts and persists `access_token` + `refresh_token`, then 302s to `<frontend>/t/{slug}/accounts?connected=1`.
7. Tokens are auto-refreshed before any X API call when expiring within 60 seconds.

Full provider setup: [docs/providers/x-twitter-setup-EN.md](docs/providers/x-twitter-setup-EN.md).

## Multi-tenancy

- Every domain row carries `organization_id`.
- A global `OrganizationScope` (registered by `BelongsToOrganization` trait) auto-filters queries when `app('currentOrganization')` is bound.
- The `tenant` middleware (`ResolveOrganization`) reads the slug from the `X-Organization-Slug` header sent by the SPA and binds the org. Membership is enforced.
- The frontend axios interceptor sets the header from `/t/{slug}` automatically.

## Background jobs

| Job | Cadence | Purpose |
|---|---|---|
| `SendScheduledPost` | per minute (per due post) | post pending tweets |
| `ProcessTargetReplies` | every 5 min | reply to monitored targets via AI |
| `FetchXmlSource` | every 15 min | turn XML/RSS values into queued posts |
| `GenerateRoutinePosts` | daily 08:00 | batch-generate AI posts for active routines |

Horizon (`make queue` dev / `scheduler` container in prod) + `schedule:work` (`make sched` dev / `scheduler` container in prod) handle this automatically.

## Roadmap

Next: Instagram + Facebook providers, real payments (PayTR/Stripe), team mode. See [docs/NEXT_PHASES_EN.md](docs/NEXT_PHASES_EN.md).
