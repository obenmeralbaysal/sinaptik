# Deployment for Dummies — Sinapp Social

This guide takes a brand-new Ubuntu server with **nginx-proxy-manager (NPM)** and gets the whole app online in under 30 minutes. No prior Docker knowledge required.

---

## 1. What you need before starting

| Item | Why |
|---|---|
| A VPS with Ubuntu 22.04+ (2 vCPU / 4 GB RAM minimum) | Run docker containers |
| `docker` + `docker compose` plugin installed | Run the stack |
| `nginx-proxy-manager` already running (usually on ports 80/443/81) | Public HTTPS + reverse proxy |
| Two DNS A records pointing to the server's IP: `social.yourdomain.com`, `api.social.yourdomain.com` | Public access |
| An X (Twitter) developer app — see `docs/providers/x-twitter-setup-EN.md` | OAuth login |
| (Optional) Gemini or OpenAI API key | AI-generated posts |

Install docker once:

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
# log out and back in
```

---

## 2. Clone the repo

```bash
cd /opt
git clone https://github.com/your-org/sinapp-social.git
cd sinapp-social
```

---

## 3. Configure the single `.env`

There is **one** `.env` at the repo root. Everything (backend, frontend, postgres, redis, mailhog) reads from it.

```bash
cp .env.example .env
nano .env
```

### Variables you MUST change

| Variable | What to put | Example |
|---|---|---|
| `APP_URL` | Full HTTPS URL of the backend | `https://api.social.yourdomain.com` |
| `FRONTEND_URL` | Full HTTPS URL of the frontend | `https://social.yourdomain.com` |
| `BACKEND_DOMAIN` | Bare backend hostname | `api.social.yourdomain.com` |
| `FRONTEND_DOMAIN` | Bare frontend hostname | `social.yourdomain.com` |
| `DB_PASSWORD` | Strong random password | `openssl rand -hex 24` |
| `REDIS_PASSWORD` | Strong random password | `openssl rand -hex 24` |
| `SESSION_DOMAIN` | Leading dot + parent domain | `.yourdomain.com` |
| `SANCTUM_STATEFUL_DOMAINS` | Comma list of frontend hosts | `social.yourdomain.com` |
| `X_CLIENT_ID` / `X_CLIENT_SECRET` | From X developer portal | — |
| `X_CALLBACK_URL` | Must match X portal exactly | `https://api.social.yourdomain.com/api/social-accounts/x/callback` |
| `AI_DRIVER` + key | `gemini` or `openai` | + `GEMINI_API_KEY=...` |
| `NEXT_PUBLIC_API_URL` | Same as `APP_URL` | `https://api.social.yourdomain.com` |
| `NEXT_PUBLIC_APP_URL` | Same as `FRONTEND_URL` | `https://social.yourdomain.com` |

### Host ports (do not change unless they clash)

Every host port ends in `11` so NPM always proxies to a predictable range:

| Service | Host port | Container port |
|---|---|---|
| Frontend (Next.js) | `3011` | 3000 |
| Backend (Laravel) | `8011` | 80 |
| PostgreSQL | `5411` | 5432 |
| Redis | `6311` | 6379 |
| MailHog SMTP | `1011` | 1025 |
| MailHog UI | `8211` | 8025 |

Leave `APP_KEY` empty — `make deploy` generates it for you.

---

## 4. One command to deploy

```bash
make deploy
```

That command:
1. Builds backend, horizon, scheduler, and frontend docker images
2. Starts postgres + redis and waits for them to be healthy
3. Generates `APP_KEY` if missing
4. Runs `php artisan migrate --force --seed`
5. Starts every service
6. Caches config / routes / views and creates the storage symlink

When it finishes you should see all 6 containers as `Up`:

```bash
make ps
```

---

## 5. Configure nginx-proxy-manager

Open NPM (usually `http://your-server:81`) and add **two Proxy Hosts**.

### Proxy host 1 — frontend

| Field | Value |
|---|---|
| Domain Names | `social.yourdomain.com` |
| Scheme | `http` |
| Forward Hostname / IP | `127.0.0.1` (or your docker host's LAN IP) |
| Forward Port | `3011` |
| Cache Assets | ✅ |
| Block Common Exploits | ✅ |
| Websockets Support | ✅ |
| SSL → Request a new Let's Encrypt certificate | ✅ |
| Force SSL + HTTP/2 | ✅ |

### Proxy host 2 — backend / API

| Field | Value |
|---|---|
| Domain Names | `api.social.yourdomain.com` |
| Scheme | `http` |
| Forward Hostname / IP | `127.0.0.1` |
| Forward Port | `8011` |
| Block Common Exploits | ✅ |
| Websockets Support | ✅ |
| SSL → Request a new Let's Encrypt certificate | ✅ |
| Force SSL + HTTP/2 | ✅ |

Under **Advanced** for the backend host, paste this so file uploads + long routine generations don't time out:

```nginx
client_max_body_size 50M;
proxy_read_timeout 300;
proxy_send_timeout 300;
```

Hit **Save**. Wait ~30 seconds for the cert.

---

## 6. Smoke test

```bash
curl -I https://api.social.yourdomain.com/api/plans
# 200 OK
```

Open `https://social.yourdomain.com`, register a user, you should land on `/t/{your-slug}/dashboard`.

---

## 7. Day-2 operations

```bash
make update          # git pull + rebuild + migrate (zero-touch upgrade)
make logs            # tail all logs
make ps              # status
make restart         # restart all containers
make shell-backend   # bash inside the Laravel container
make pg-shell        # psql
make horizon-status  # Horizon queue health
make fresh           # DESTRUCTIVE: wipe DB and reseed
```

---

## 8. Backups

Postgres data lives in the `pgdata` named volume.

```bash
docker run --rm -v sinapp-social_pgdata:/data -v $PWD:/backup alpine \
    tar czf /backup/pgdata-$(date +%F).tar.gz -C /data .
```

Schedule that via a cron job.

---

## 9. Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| NPM shows `502 Bad Gateway` | Container down or wrong port | `make ps`, then `make logs` |
| `CORS error` in browser console | `SANCTUM_STATEFUL_DOMAINS` / `SESSION_DOMAIN` mismatch | Re-edit `.env`, `make restart` |
| Login succeeds but `/me` returns 401 | Cookie domain wrong | `SESSION_DOMAIN=.yourdomain.com` (leading dot) |
| X OAuth: `redirect_uri mismatch` | `X_CALLBACK_URL` differs from X portal | Make them byte-identical |
| Horizon shows no jobs | Worker not running | `make ps` (horizon should be Up), `make logs` |
| `php artisan: APP_KEY missing` | Skipped step | `make shell-backend` → `php artisan key:generate --force` |
| Frontend shows raw text / no CSS | `output: 'standalone'` build incomplete | `make build-frontend && make restart` |
| `pg_isready` never green | Wrong `DB_PASSWORD` between `.env` and old volume | `docker compose down -v` (DESTROYS data) and re-deploy |

---

## 10. Updating to a new version

```bash
cd /opt/sinapp-social
make update
```

That's it. No downtime longer than ~10 seconds per service.
