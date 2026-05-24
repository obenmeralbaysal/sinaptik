# Sinapp Social — Roadmap / Next Phases

Phase 1 (done): X (Twitter) provider, AI (Gemini/OpenAI), routines, XML sources, multi-tenant SPA, admin, Sanctum SPA auth, Horizon, Dockerized deployment behind nginx-proxy-manager.

Below is the ordered backlog. Each phase is sized to be 1-3 weeks of work.

---

## Phase 2 — Instagram & Facebook providers

**Goal:** customers can connect IG Business + FB Page accounts and schedule posts to them, same as X.

- Implement `App\Services\Instagram\InstagramClient` and `InstagramOAuthService` against `App\Contracts\SocialProvider`.
- Implement `App\Services\Facebook\FacebookClient` and `FacebookOAuthService`.
- Add controller methods `instagramConnect/instagramCallback`, `facebookConnect/facebookCallback` to `SocialAccountController`.
- Page selection screen after FB OAuth (one user, N pages → pick which one to connect).
- Switch `FILESYSTEM_DISK` to `s3` (or DigitalOcean Spaces / Cloudflare R2) in prod so IG can fetch media URLs.
- Long-lived token refresh job (every 50 days).
- Frontend: provider tabs on `/t/{slug}/accounts`, generic `<ConnectProviderButton provider="instagram">` component.
- Tests: PKCE/OAuth happy paths + token expiry handling.

See: `docs/providers/instagram-setup-EN.md`, `docs/providers/facebook-setup-EN.md`.

---

## Phase 3 — Payments (real billing)

**Goal:** turn `NullGateway` into actual revenue.

- Implement `App\Services\Payments\PaytrGateway` for TR market (3D Secure, Iyzico-style hosted page).
- Implement `App\Services\Payments\StripeGateway` for international.
- Bind correct gateway in `AppServiceProvider` based on `PAYMENT_GATEWAY` env var.
- Plans CRUD already exists in admin (`/api/admin/plans`). Add:
  - `subscription` lifecycle on `Organization` (trialing / active / past_due / canceled).
  - Webhook endpoints for both gateways.
  - Quota enforcement middleware (post count vs. plan limit).
- Frontend: `/t/{slug}/billing` page, plan picker, invoice list, "upgrade" CTAs when quota hit.

---

## Phase 4 — Multi-member tenants (team mode)

**Goal:** an organization can invite teammates with roles.

- Pivot `organization_user` already has `role` column. Add invitation flow:
  - `POST /api/orgs/{slug}/invitations` (admin only)
  - Signed e-mail with accept link
  - `POST /api/invitations/{token}/accept` (auth-required, joins org)
- Roles using spatie/laravel-permission: `owner`, `admin`, `editor`, `viewer`.
- Per-resource policies (e.g. only `admin+` can disconnect a social account).
- Frontend: `/t/{slug}/settings/team` page (invite, change role, remove).

---

## Phase 5 — Production mail + Analytics

- Mail: switch `MAIL_MAILER` to `resend` or `sendgrid` in prod, add transactional templates (welcome, invitation, password reset, weekly report).
- Analytics ingestion:
  - After each X/IG/FB post sends, schedule a metrics fetch at +1h, +24h, +7d.
  - Store snapshots in `post_metrics` table (impressions, likes, replies, reposts).
- Frontend: `/t/{slug}/analytics` dashboard with line charts (Recharts), per-post drill-down, CSV export.

---

## Phase 6 — More providers

In priority order based on TR market demand:

1. **LinkedIn** (Marketing Developer Platform — has long approval cycle, start early).
2. **TikTok for Business** (Content Posting API).
3. **Threads** (Meta — easy, piggybacks on FB app).
4. **YouTube Shorts** (Google Cloud OAuth + YouTube Data API v3, requires app verification).

Each follows the same `SocialProvider` contract + `OAuthService` pattern established in Phase 2.

---

## Phase 7 — AI upgrades

- Image generation: integrate DALL-E 3 / Google Imagen via the existing `AIDriver` contract — add `generateImage()` method.
- Video posts: short-form vertical video generation (Runway / Sora API once GA), then schedule to IG Reels + TikTok + Shorts.
- Scheduling assistant: AI suggests optimal posting times per account based on historical metrics from Phase 5.
- Voice & brand guidelines: per-organization tone/voice config that's prepended to every prompt (already partially in `ai-settings`).

---

## Phase 8 — Public API + White-label

- **Public API**: expose the same endpoints under `/v1/` with Bearer-token auth (Sanctum personal access tokens). Rate-limit per token. Publish OpenAPI spec auto-generated via `darkaonline/l5-swagger`.
- **White-label / reseller mode**: agencies can customize logo, primary color, login screen, custom domain (CNAME → frontend). Add `Brand` model + middleware that injects branding into the SPA.
- Zapier / n8n integration triggered by Sinapp webhooks (`post.published`, `reply.sent`, `quota.warning`).

---

## Phase 9 — Hardening

- Multi-region read replica for Postgres.
- Backup automation + restore drill (postgres + S3).
- Sentry for backend + frontend error tracking.
- Rate-limit + WAF rules at NPM / Cloudflare.
- SOC 2 prep: audit log table for every privileged action, data retention policy doc, GDPR/KVKK data-export endpoint.

---

## Items intentionally NOT on the roadmap

- Native mobile apps (PWA is sufficient for v1-v3).
- Direct ad campaign management (Meta Ads API, X Ads API) — out of scope; integrate Adscanner-style read-only later.
- Cross-poster magic (one input, many networks) until each provider's quirks are fully understood.
