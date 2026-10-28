# Deploy — Analytics

Analytics is not in production from this monorepo. There is no cutover. Compose context is `apps/analytics`. The app `.env` is `apps/analytics/.env` (see `.env.example`). Keep it separate from Social. Do not commit secrets.

Host ports in `.env.example` bind `127.0.0.1` only and do not use Social’s `5432` / `6379`.

```bash
cd /path/to/sinaptik
git pull origin main
cd apps/analytics
cp .env.example .env   # first time only; fill APP_KEY, DB_PASSWORD, REDIS_PASSWORD on the server
docker compose up -d --build
```

Tests do not need that stack: from `apps/analytics`, `make test` runs PHPUnit on sqlite memory, and `cd frontend && npm run typecheck` checks the UI.
