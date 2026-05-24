# API Reference for Dummies — Sinapp Social

Base URL (production): `https://api.social.yourdomain.com`
Base URL (local dev):  `http://localhost:8011`

All endpoints live under `/api`.

---

## 0. Concepts you must understand first

### Authentication = Sanctum SPA cookies
This is **not** a Bearer-token API for third parties (yet — see `docs/NEXT_PHASES_EN.md`). The frontend talks to the API via session cookies set by `POST /api/login`. From `curl`, persist cookies with `-c cookies.txt -b cookies.txt`.

### Multi-tenancy = `X-Organization-Slug` header
Every tenant-scoped endpoint requires the header:

```
X-Organization-Slug: your-org-slug
```

The middleware `ResolveOrganization` reads this header, looks up the organization, asserts you're a member, and auto-filters all queries via the `OrganizationScope` global scope. Forget the header → `403`.

### Response shape
All resources are returned as JSON (Laravel API Resources). Errors follow Laravel's standard:

```json
{ "message": "human readable", "errors": { "field": ["..."] } }
```

### Common status codes
| Code | Meaning |
|---|---|
| `200` | OK |
| `201` | Created |
| `204` | No content (delete) |
| `401` | Not logged in |
| `403` | Logged in but no access (wrong tenant, no admin role, etc.) |
| `404` | Resource missing or scoped out by the tenant filter |
| `422` | Validation failed (`errors` object tells you which fields) |

---

## 1. Auth

### `POST /api/register`  *(public)*
Create a user + their first organization.

```bash
curl -c cookies.txt -X POST https://api.social.yourdomain.com/api/register \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Oben",
    "email": "oben@example.com",
    "password": "secret1234",
    "password_confirmation": "secret1234",
    "organization_name": "Sinaptik"
  }'
```

Response `201`:
```json
{ "user": {...}, "organization": { "id": 1, "slug": "sinaptik", "name": "Sinaptik" } }
```

### `POST /api/login`  *(public)*
```bash
curl -c cookies.txt -X POST https://api.social.yourdomain.com/api/login \
  -H 'Content-Type: application/json' \
  -d '{ "email": "oben@example.com", "password": "secret1234" }'
```
Sets session cookie. Response `200` with user.

### `POST /api/logout`  *(auth)*
```bash
curl -b cookies.txt -X POST https://api.social.yourdomain.com/api/logout
```

### `GET /api/me`  *(auth)*
Returns the authenticated user + their organizations + current one.

### `POST /api/me/switch-organization`  *(auth)*
```bash
curl -b cookies.txt -X POST .../api/me/switch-organization \
  -H 'Content-Type: application/json' \
  -d '{ "organization_id": 2 }'
```

---

## 2. Plans  *(public)*

### `GET /api/plans`
Returns plans available for signup.

```bash
curl https://api.social.yourdomain.com/api/plans
```

---

## 3. Organization (tenant-self)

All routes here require `auth:sanctum` + `tenant` middleware → send `X-Organization-Slug`.

| Method | Path | What |
|---|---|---|
| `GET`   | `/api/orgs/{orgSlug}`              | Fetch your org |
| `PATCH` | `/api/orgs/{orgSlug}`              | Update name / slug / settings |
| `GET`   | `/api/orgs/{orgSlug}/members`      | List members |
| `GET`   | `/api/orgs/{orgSlug}/ai-settings`  | AI driver + system prompt |
| `PATCH` | `/api/orgs/{orgSlug}/ai-settings`  | Update AI driver / prompt / model |

Example:
```bash
curl -b cookies.txt \
  -H 'X-Organization-Slug: sinaptik' \
  https://api.social.yourdomain.com/api/orgs/sinaptik
```

---

## 4. Social accounts

| Method | Path | What |
|---|---|---|
| `GET`    | `/api/social-accounts`           | List connected accounts |
| `GET`    | `/api/social-accounts/{id}`      | Show one |
| `DELETE` | `/api/social-accounts/{id}`      | Disconnect |
| `POST`   | `/api/social-accounts/x/connect` | Begin X OAuth — returns `auth_url`, redirect the user there |
| `GET`    | `/api/social-accounts/x/callback`| **X redirects the user here** (do NOT call yourself) |

X connect flow:

```bash
curl -b cookies.txt -X POST \
  -H 'X-Organization-Slug: sinaptik' \
  https://api.social.yourdomain.com/api/social-accounts/x/connect
# → { "auth_url": "https://x.com/i/oauth2/authorize?..." }
```

Then the browser hits `auth_url`, X redirects back to `/api/social-accounts/x/callback?code=...&state=...`, the backend exchanges the code, stores encrypted tokens, and 302s the user to `https://social.yourdomain.com/t/{slug}/accounts?connected=1`.

---

## 5. Targets (accounts/keywords you reply to)

`apiResource('targets')` →

| Method | Path |
|---|---|
| `GET`    | `/api/targets` |
| `POST`   | `/api/targets` |
| `GET`    | `/api/targets/{target}` |
| `PATCH`  | `/api/targets/{target}` |
| `DELETE` | `/api/targets/{target}` |
| `POST`   | `/api/targets/{target}/preview` — preview an AI reply without sending |

Create:
```bash
curl -b cookies.txt -X POST \
  -H 'X-Organization-Slug: sinaptik' \
  -H 'Content-Type: application/json' \
  -d '{ "handle": "@laravel", "type": "user", "reply_prompt": "be helpful" }' \
  https://api.social.yourdomain.com/api/targets
```

---

## 6. Posts

`apiResource('posts')` plus:

| Method | Path | What |
|---|---|---|
| `POST` | `/api/posts/{post}/send-now`   | Dispatch immediately |
| `POST` | `/api/posts/bulk-schedule`     | Schedule many in one call |

Create a scheduled post:
```bash
curl -b cookies.txt -X POST \
  -H 'X-Organization-Slug: sinaptik' \
  -H 'Content-Type: application/json' \
  -d '{
    "social_account_id": 1,
    "body": "hello world",
    "scheduled_at": "2026-06-01T10:00:00Z"
  }' \
  https://api.social.yourdomain.com/api/posts
```

---

## 7. Routines (AI-generated daily content)

`apiResource('routines')` plus:

| Method | Path | What |
|---|---|---|
| `POST` | `/api/routines/generate`        | Generate today's batch now |
| `POST` | `/api/routines/regenerate-one`  | Regenerate one slot |
| `GET`  | `/api/routines/_meta/models`    | List available AI models for your driver |

---

## 8. XML sources (RSS-driven posts)

`apiResource('xml-sources')` plus:

| Method | Path | What |
|---|---|---|
| `POST` | `/api/xml-sources/{xml_source}/run` | Fetch + queue right now |

---

## 9. Admin  *(super-admin role only)*

Guard: `can:access-admin`.

| Method | Path | What |
|---|---|---|
| `GET`    | `/api/admin/stats`        | Counts (orgs/users/posts/revenue) |
| `GET`    | `/api/admin/users`        | All users (paginated) |
| `GET`    | `/api/admin/plans`        | All plans |
| `POST`   | `/api/admin/plans`        | Create plan |
| `PATCH`  | `/api/admin/plans/{plan}` | Update plan |
| `DELETE` | `/api/admin/plans/{plan}` | Delete plan |

```bash
curl -b cookies.txt https://api.social.yourdomain.com/api/admin/stats
```

---

## 10. Quick recipe — full happy path

```bash
# 1. register
curl -c jar -X POST .../api/register -H 'Content-Type: application/json' \
  -d '{"name":"O","email":"o@x.io","password":"secret1234","password_confirmation":"secret1234","organization_name":"Acme"}'

# 2. start X OAuth (browser will follow auth_url)
curl -b jar -X POST -H 'X-Organization-Slug: acme' .../api/social-accounts/x/connect

# 3. create a target
curl -b jar -X POST -H 'X-Organization-Slug: acme' -H 'Content-Type: application/json' \
  -d '{"handle":"@laravel","type":"user"}' .../api/targets

# 4. schedule a post
curl -b jar -X POST -H 'X-Organization-Slug: acme' -H 'Content-Type: application/json' \
  -d '{"social_account_id":1,"body":"gm","scheduled_at":"2026-06-01T08:00:00Z"}' .../api/posts
```

That's all 95 % of integrations need.
