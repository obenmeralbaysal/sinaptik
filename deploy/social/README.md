# Deploy — Social

Live Social stays on **`git@github.com:obenmeralbaysal/sinapp-social.git` @ `e578ed1`** until a separate cutover is approved. Rollback is to keep deploying that repo. Tag `social-live-e578ed1` in this monorepo is only the rewritten copy of that commit under `apps/social`.

When cutover is approved, devops works inside this app directory. Compose context is `apps/social` (project name `sinsocial`). The app `.env` is `apps/social/.env`, separate from other apps. Never commit it.

```bash
cd /path/to/sinaptik
git pull origin main
cd apps/social
cp .env.example .env   # first time only; fill secrets on the server
chmod +x deploy/deploy.sh deploy/backup.sh docker/postgres/init.sh
./deploy/deploy.sh
```

`deploy/deploy.sh` is the existing Social script. It builds images, waits for Postgres and Redis, and checks health on the host port from `.env`. Postgres and Redis are not published publicly. NPM still forwards `social.sinaptik.com.tr` to `sinsocial-web`.

Do not run this against the current production checkout while it still tracks `sinapp-social`.
