# Deploy — Ads Manager

`apps/adsmanager` is a placeholder. There is no application code and no Compose file. The product plan is `apps/adsmanager/docs/adsmanager-plan.md`.

After the app exists, devops will `git pull` in the monorepo and work only inside `apps/adsmanager`, with that app’s own `.env` and its own Compose project. Do not copy Social or Analytics secrets into it. Do not deploy until cutover is approved.
