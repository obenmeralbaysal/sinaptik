# Facebook (Pages API) Setup — For Dummies

To post to a customer's **Facebook Page** you need a Meta app with the **Facebook Login + Pages API** products. The same Meta app can host both the Instagram and Facebook integrations.

> Status in Sinapp Social: env placeholders ready (`FACEBOOK_APP_ID`, `FACEBOOK_APP_SECRET`), provider implementation is **Phase 2** (see `docs/NEXT_PHASES_EN.md`).

---

## 1. Use the same Meta app you made for Instagram

If you already followed `docs/providers/instagram-setup-EN.md`, **you do not need a second app**. Just add the Facebook Login product to it. If you skipped that doc, do steps 1-4 there first, then come back.

---

## 2. Add Facebook Login

1. App dashboard → **Add Product** → **Facebook Login** → Set up.
2. Choose **Web**.
3. Site URL: `https://social.yourdomain.com`.

In **Facebook Login → Settings**:

| Field | Value |
|---|---|
| Client OAuth Login | ✅ Yes |
| Web OAuth Login | ✅ Yes |
| Use Strict Mode for redirect URIs | ✅ Yes |
| Valid OAuth Redirect URIs | `https://social.yourdomain.com/api/social-accounts/facebook/callback` |

---

## 3. Permissions you'll request

| Permission | Purpose |
|---|---|
| `email`                              | Account identity |
| `public_profile`                     | Name + avatar |
| `pages_show_list`                    | List the user's Pages |
| `pages_read_engagement`              | Read post metrics |
| `pages_manage_posts`                 | **Publish posts on behalf of the Page** |
| `pages_manage_metadata`              | Subscribe webhooks |
| `pages_messaging` *(if you add inbox)* | Reply to DMs |
| `business_management`                | Business Manager access |

`pages_manage_posts` requires **App Review** + Meta business verification.

---

## 4. App credentials → `.env`

Settings → **Basic**:

```ini
FACEBOOK_APP_ID=<paste>
FACEBOOK_APP_SECRET=<paste>
FACEBOOK_CALLBACK_URL=https://social.yourdomain.com/api/social-accounts/facebook/callback
```

`make restart`.

---

## 5. The Page Access Token model (important!)

OAuth gives you a **User Access Token**. You don't post with it. The flow is:

1. User logs in via Facebook OAuth → backend receives user token.
2. Backend calls `GET /me/accounts` → returns the user's Pages **each with its own Page Access Token**.
3. Sinapp stores the selected Page's `page_id` + `page_access_token` (encrypted) on `social_accounts`.
4. All posting calls use the **Page token**, not the user token: `POST /{page-id}/feed`.

Page tokens issued by a long-lived user token are themselves **non-expiring** for as long as the user doesn't revoke or change password.

---

## 6. App Review — what reviewers want

For `pages_manage_posts`:

1. 60-sec screencast: user logs in → picks Page → publishes a test post from Sinapp.
2. Reviewer credentials: a Facebook account with at least one Page they admin.
3. Business verification: upload company documents in **Business Settings**. Takes ~3 business days.
4. Detailed description of how you store the token (encrypted) and when you delete it (on disconnect / 30 days after last login — be honest).

---

## 7. Webhooks (optional but recommended)

To get real-time notifications (new comments, mentions) instead of polling:

1. App → **Webhooks** → **Page** object.
2. Callback URL: `https://social.yourdomain.com/api/webhooks/facebook`
3. Verify token: random string, copy into `.env` as `FACEBOOK_WEBHOOK_VERIFY_TOKEN` (Phase 2).
4. Subscribe to `feed`, `mention`, `messages`.

---

## 8. Common errors

| Error | Cause | Fix |
|---|---|---|
| `(#200) Permissions error` | Missing `pages_manage_posts` for this page | Re-do OAuth and select the Page explicitly |
| `(#190) Invalid OAuth access token` | Token revoked / password changed | User reconnects |
| `URL blocked: This redirect failed` | Redirect URI not in Valid OAuth list | Add the exact URL in Facebook Login settings |
| `App Not Set Up: This app is still in development mode` | App in Dev mode for non-testers | Move to Live + finish App Review |
| `(#100) Tried accessing nonexisting field` | Wrong Graph API version | Use `v19.0` or newer in service calls |
| `Business verification required` | `pages_manage_posts` without verified business | Submit business documents in Meta Business Suite |

---

## 9. Page roles you need on a customer's Page

To post on a Page via the API, the connecting user must be at least **Editor** on that Page (better: **Admin**). If they're only **Moderator** or **Advertiser**, posting will silently fail.
