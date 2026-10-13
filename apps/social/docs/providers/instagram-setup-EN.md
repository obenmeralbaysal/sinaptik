# Instagram (Graph API) Setup — For Dummies

Instagram does **not** have a standalone developer API anymore. You go through **Meta for Developers** + the **Instagram Graph API** (for Business / Creator accounts only). Personal accounts cannot post via API.

> Status in Sinapp Social: env placeholders ready (`INSTAGRAM_APP_ID`, `INSTAGRAM_APP_SECRET`), provider implementation is **Phase 2** (see `docs/NEXT_PHASES_EN.md`).

---

## 1. Pre-requisites for the END user

The customer's Instagram account must be:
1. **Business** or **Creator** (Settings → Account → Switch account type).
2. Connected to a **Facebook Page** they admin.

If either of these is missing, the API will refuse them — no workaround.

---

## 2. Create a Meta developer account

1. Go to <https://developers.facebook.com>.
2. Click **Get Started** with your personal Facebook login.
3. Verify your phone number.
4. Accept the developer policies.

---

## 3. Create an App

1. **My Apps** → **Create App**.
2. Use case: **Other** → app type: **Business**.
3. App name: `Sinapp Social` — contact email: yours.
4. Create.

---

## 4. Add the right products

In your app's left sidebar → **Add Product**:

1. **Instagram** → Set up.
2. **Facebook Login for Business** → Set up.

---

## 5. Configure Instagram Graph API

Inside **Instagram** product settings → **API setup with Instagram Business Login**.

| Field | Value |
|---|---|
| OAuth Redirect URIs | `https://social.yourdomain.com/api/social-accounts/instagram/callback` |
| Deauthorize Callback URL | `https://social.yourdomain.com/api/social-accounts/instagram/deauthorize` |
| Data Deletion Request URL | `https://social.yourdomain.com/api/social-accounts/instagram/data-deletion` |

Required permissions (you will request these during App Review):

- `instagram_basic`
- `instagram_content_publish`
- `instagram_manage_comments`
- `instagram_manage_insights`
- `pages_show_list`
- `pages_read_engagement`
- `business_management`

---

## 6. Get App ID and App Secret

Settings → **Basic**. Copy into `.env`:

```ini
INSTAGRAM_APP_ID=<paste>
INSTAGRAM_APP_SECRET=<paste>
INSTAGRAM_CALLBACK_URL=https://social.yourdomain.com/api/social-accounts/instagram/callback
```

`make restart`.

---

## 7. App Review (required to go live)

While in **Development** mode, only people listed under **Roles** (admins, testers, developers) can connect. To accept real customers you must submit each permission for **App Review**:

1. **App Review** → **Permissions and Features** → request each scope from §5.
2. For each one upload a 60-second screencast that shows:
   - User clicks "Connect Instagram" in your SPA
   - Facebook login + page selection
   - The data being used (e.g. posting a test caption + image)
3. Provide a test user (Facebook account with a linked Business IG) for Meta's reviewers.
4. Wait 5–14 days.

Once approved, switch the app to **Live** mode.

---

## 8. Required infrastructure for posting

To publish an Instagram post via the API you must:

1. Upload the image to a **publicly reachable HTTPS URL** (Meta fetches it server-side). Sinapp will use Laravel's `public` disk + your CDN — set `FILESYSTEM_DISK=s3` and configure an S3-compatible bucket for prod.
2. Call `POST /{ig-user-id}/media` to create a container.
3. Call `POST /{ig-user-id}/media_publish` to publish it.

Videos and carousels follow the same two-step "container → publish" pattern.

---

## 9. Long-lived tokens

Short-lived user tokens expire in 1 hour. Sinapp will:

1. Exchange them for a **60-day long-lived token** immediately after OAuth callback.
2. Re-exchange every 50 days via a scheduled job to never expire.

If a user revokes access from `https://accessibility.facebook.com/?tab=apps_and_websites`, the token dies — Sinapp will surface a re-connect banner.

---

## 10. Common errors

| Error | Cause | Fix |
|---|---|---|
| `(#10) Application does not have permission` | App still in Dev mode for a non-tester | Submit App Review or add the user as Tester |
| `Invalid OAuth access token` | Token expired or revoked | Have user reconnect |
| `Media URL is not reachable` | Image not publicly accessible | Use S3/public disk + check CORS |
| `Page is not Business` | User's IG not Business/Creator | They must convert + connect to a Page |
| `Invalid platform app` | Wrong App ID/Secret pair | Re-copy from Settings → Basic |
