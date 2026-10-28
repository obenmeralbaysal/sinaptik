# sinaptik

Monorepo for Sinaptik products. Each app keeps its own history, Compose project, and `.env`. Nothing in this repo is a production cutover.

| Path | What it is |
| --- | --- |
| [apps/social](apps/social) | Sinaptik Social (imported from `obenmeralbaysal/sinapp-social`, including `feat/consent-meta`) |
| [apps/analytics](apps/analytics) | Sinaptik Analytics (local history, including `feat/consent-google`) |
| [apps/web](apps/web) | Marketing site placeholder. No site code yet. |
| [apps/adsmanager](apps/adsmanager) | Ads Manager placeholder. Plan: [apps/adsmanager/docs/adsmanager-plan.md](apps/adsmanager/docs/adsmanager-plan.md) |
| [packages/shared](packages/shared) | Empty shared-package scaffold |
| [deploy](deploy) | Per-app notes for a later, approved cutover |

## Live production

Social production still deploys from **`obenmeralbaysal/sinapp-social`** at **`e578ed1`** (`feat/saas-foundation`: “Fix the test-deploy image build and frontend healthcheck.”). This monorepo does not replace that remote. Do not point the VPS at `sinaptik` until a separate cutover is approved.

The filtered copy of that live commit is tag **`social-live-e578ed1`**. Rollback for Social is to keep using `sinapp-social`, not to redeploy this repo.

Historical branches (rewritten so trees live under `apps/…`; SHAs differ from the original repos):

- `feat/consent-meta` — Social consent tip (`apps/social`)
- `feat/saas-foundation` — Social live tree under `apps/social`
- `legacy/pre-saas-2026-10-17` — pre-SaaS Social tip
- `feat/consent-google` — Analytics consent tip (`apps/analytics`)

## CI

[`.github/workflows/ci.yml`](.github/workflows/ci.yml) runs a job only when that app’s paths change. Social and Analytics jobs document the local test commands; they do not start databases in GitHub Actions.
