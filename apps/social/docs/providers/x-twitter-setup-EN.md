# X (Twitter) Developer Account Setup — For Dummies

You need an X developer app with **OAuth 2.0 + PKCE** enabled so users can connect their X accounts to Sinapp Social.

---

## 1. Create a developer account

1. Go to <https://developer.x.com/en/portal/dashboard>.
2. Sign in with the X account that will OWN the app (use a company account, not a personal throwaway).
3. Click **Sign up for Free Account** (or Basic / Pro if you need higher rate limits — Free is enough for testing).
4. Fill the use-case form. Honest 1-paragraph answer is fine:
   > "We are building a SaaS that lets businesses schedule posts and reply to mentions via the X API. We only post on behalf of users who explicitly connect their account through OAuth 2.0."
5. Confirm your email if prompted.

---

## 2. Create a Project + App

1. In the portal → **Projects & Apps** → **Add Project**.
   - Name: `Sinapp Social`
   - Use case: `Building tools for businesses`
2. Inside that project → **Add App** → name it `Sinapp Social Prod` (and later make a separate one for dev).

---

## 3. Enable OAuth 2.0 (this is the critical part)

In the app settings, find **User authentication settings** → **Set up**.

| Field | Value |
|---|---|
| App permissions | **Read and write** |
| Type of App | **Web App, Automated App or Bot** |
| **OAuth 2.0** | ✅ enabled |
| OAuth 1.0a | ❌ leave off (not used) |
| Callback URI / Redirect URL | `https://api.social.yourdomain.com/api/social-accounts/x/callback` |
| Website URL | `https://social.yourdomain.com` |
| Terms of service / Privacy policy URL | your URLs (required for prod) |

**Save**. You'll be shown a Client ID and Client Secret **ONCE** — copy them now into `.env`:

```ini
X_CLIENT_ID=<paste>
X_CLIENT_SECRET=<paste>
X_CALLBACK_URL=https://api.social.yourdomain.com/api/social-accounts/x/callback
```

Restart the backend:
```bash
make restart
```

---

## 4. Scopes used by Sinapp

The app asks the user for:

| Scope | Why |
|---|---|
| `tweet.read`     | Read user's tweets and mentions |
| `tweet.write`    | Post scheduled tweets |
| `users.read`     | Fetch profile info |
| `offline.access` | Get a refresh token so we never lose access |

These are hard-coded in `App\Services\X\XOAuthService` — you do not need to enter them in the X portal.

---

## 5. Test the connection

1. Log in to `https://social.yourdomain.com` and switch to your tenant.
2. Go to **Accounts** → click **Connect X account**.
3. You'll be sent to `x.com/i/oauth2/authorize?...` → click **Authorize app**.
4. X redirects you back. You should see your handle + avatar listed.

---

## 6. Rate limits

| Tier | Posts / month | Cost |
|---|---|---|
| Free   | 1,500 / user / month | $0 |
| Basic  | 50,000 / month       | $200 |
| Pro    | 1,000,000 / month    | $5,000 |

Sinapp's scheduler is rate-limit-aware but on Free you'll cap fast with multiple tenants.

---

## 7. Common errors

| Error | Cause | Fix |
|---|---|---|
| `redirect_uri does not match` | `X_CALLBACK_URL` differs from portal | Make them byte-identical (https, no trailing slash) |
| `Unauthorized client` | OAuth 2.0 not enabled | Re-do step 3, make sure OAuth 2.0 toggle is ON |
| `invalid_scope` | Old app without v2 scopes | Recreate the app from scratch |
| `Could not authenticate you` | Wrong Client Secret in `.env` | Re-generate the secret in the portal, update `.env`, `make restart` |
| Token expired after 2h, no auto-refresh | Missing `offline.access` scope | Disconnect + reconnect; Sinapp always requests it |

---

## 8. Production checklist

- [ ] App is in **Production** environment, not Development
- [ ] Privacy policy + ToS URLs filled in (X reviews this)
- [ ] You have **two separate apps**: one for `social.yourdomain.com` (prod) and one for `localhost` / staging
- [ ] `.env` on the server has `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Make sure `X_CALLBACK_URL` uses `https://`, not `http://`
