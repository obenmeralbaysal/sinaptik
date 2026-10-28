# Deploy

These notes are for a future cutover. This repo does not deploy, and it does not SSH to any host.

Each app is deployed from its own directory with its own `.env`. Secrets stay on the server. Do not commit `.env` files.

| App | Compose / work directory | Notes |
| --- | --- | --- |
| Social | `apps/social` | [social/README.md](social/README.md) |
| Analytics | `apps/analytics` | [analytics/README.md](analytics/README.md) |
| Web | `apps/web` | [web/README.md](web/README.md) |
| Ads Manager | `apps/adsmanager` | [adsmanager/README.md](adsmanager/README.md) |

## Social production (current)

Live Social is still **`obenmeralbaysal/sinapp-social` @ `e578ed1`**. Do not switch the VPS remote to `obenmeralbaysal/sinaptik` until cutover is approved. Rollback is to keep using the old repo and the existing Compose project on the server.
