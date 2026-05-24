# Sinapp Social v2

Multi-tenant social media automation. Laravel 11 (headless API) + Next.js 15 (App Router).

- **Production:** `social.sinaptik.com.tr` (frontend), `api.social.sinaptik.com.tr` (API)
- **Tenant URL pattern:** `/t/{slug}` (e.g. `/t/obenmeralbaysal`)
- **First integration:** X (Twitter) via OAuth 2.0 + PKCE (S256)

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 11, Sanctum (SPA cookies), Horizon, spatie/laravel-permission |
| Frontend | Next.js 15 (App Router), Tailwind v4, TanStack Query, Axios, shadcn-style UI primitives, next-themes |
| Database | PostgreSQL 16 |
| Cache / Queue / Session | Redis 7 |
| Mail (dev) | MailHog |

## Folder layout

```
sinapp-social/
├── backend/          # Laravel 11 API
├── frontend/         # Next.js 15 SPA
├── docker-compose.yml  # postgres + redis + mailhog
├── Makefile
└── README.md
```

## First-time setup

Prereqs: Docker, PHP 8.2 with `pdo_pgsql`, Composer 2, Node 20+.

```bash
make setup            # boots postgres+redis+mailhog, installs deps, runs migrations + seeders
```

Open two terminals:

```bash
make api              # Laravel on http://localhost:8000
make web              # Next.js on http://localhost:3000
```

For background processing:

```bash
make queue            # Horizon worker
make sched            # scheduler tick (or use system cron in prod)
```

## Configuration

Backend `.env` (see `backend/.env.example`):

```ini
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000
DB_CONNECTION=pgsql
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000

X_CLIENT_ID=
X_CLIENT_SECRET=
X_CALLBACK_URL=http://localhost:8000/api/social-accounts/x/callback

AI_DRIVER=gemini    # gemini | openai
GEMINI_API_KEY=
OPENAI_API_KEY=
```

Frontend `.env.local` (see `frontend/.env.example`):

```
NEXT_PUBLIC_API_URL=http://localhost:8000
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

In production set:

- Frontend `NEXT_PUBLIC_API_URL=https://api.social.sinaptik.com.tr`
- Backend `APP_URL=https://api.social.sinaptik.com.tr`, `FRONTEND_URL=https://social.sinaptik.com.tr`, `SANCTUM_STATEFUL_DOMAINS=social.sinaptik.com.tr`, `SESSION_DOMAIN=.sinaptik.com.tr`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`.

## How OAuth (X) works

1. User signs in to the SPA, lands on `/t/{slug}/accounts`.
2. Click *Connect X account* → SPA POSTs `/api/social-accounts/x/connect` (auth + tenant required).
3. Backend generates `code_verifier` (random 64 bytes), `code_challenge = base64url(sha256(verifier))`, signs a `state`, caches `{organization_id, verifier}` for 15 minutes, returns `auth_url`.
4. SPA redirects browser to `auth_url` on `x.com/i/oauth2/authorize`.
5. X redirects to `GET /api/social-accounts/x/callback?code=...&state=...` (no auth needed; state binds the org).
6. Backend exchanges the code, fetches the user profile, encrypts and persists `access_token` + `refresh_token` on `social_accounts`, then 302s to `<frontend>/t/{slug}/accounts?connected=1`.
7. Tokens are auto-refreshed before any X API call when expiring within 60 seconds.

## Multi-tenancy

- Every domain row carries `organization_id`.
- A global `OrganizationScope` (registered by `BelongsToOrganization` trait) auto-filters queries when `app('currentOrganization')` is bound.
- The `tenant` middleware (`ResolveOrganization`) reads the slug from the `X-Organization-Slug` header sent by the SPA and binds the org. Membership is enforced.
- The frontend axios interceptor sets the header from `/t/{slug}` automatically.

## Adding a new social provider

1. Implement `App\Contracts\SocialProvider` (see `App\Services\X\XClient`).
2. Add an OAuth service mirroring `App\Services\X\XOAuthService`.
3. Add `connect` + `callback` endpoints in `SocialAccountController` (or split per provider).
4. Add a frontend connect button on `/t/{slug}/accounts`.

## Background jobs

| Job | Cadence | Purpose |
|---|---|---|
| `SendScheduledPost` | per minute (per due post) | post pending tweets |
| `ProcessTargetReplies` | every 5 min | reply to monitored targets via AI |
| `FetchXmlSource` | every 15 min | turn XML/RSS values into queued posts |
| `GenerateRoutinePosts` | daily 08:00 | batch-generate AI posts for active routines |

Run with `make queue` (Horizon) and `make sched` (scheduler) in dev. In prod, run a Horizon supervisor and a system cron entry: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`.

## Stubs / TODO

- `App\Services\Payments\NullGateway` is the default. Bind a concrete `PaymentGateway` (PayTR / Stripe) in `AppServiceProvider` when ready.
- Mail uses Laravel's default mailer (log driver in dev). Wire SendGrid/Resend in production.
- Multi-member tenant invites: pivot `organization_user` already supports roles; UI needs to be added.
