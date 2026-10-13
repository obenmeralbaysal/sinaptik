# Instagram (Graph API) Kurulumu — Yeni Başlayanlar İçin

Instagram'ın artık bağımsız bir developer API'ı **yok**. Her şey **Meta for Developers** + **Instagram Graph API** üzerinden (yalnızca Business / Creator hesaplarla). Kişisel hesaplar API ile post atamaz.

> Sinapp Social'daki durum: env placeholder'ları hazır (`INSTAGRAM_APP_ID`, `INSTAGRAM_APP_SECRET`), provider implementasyonu **Faz 2** (bkz. `docs/NEXT_PHASES_TR.md`).

---

## 1. Son kullanıcının ön koşulları

Müşterinin Instagram hesabı şunlar olmalı:
1. **Business** veya **Creator** (Ayarlar → Hesap → Hesap türünü değiştir).
2. Yönetici olduğu bir **Facebook Page**'e bağlı.

Bunlardan biri eksikse API kabul etmez — başka yolu yok.

---

## 2. Meta developer hesabı aç

1. <https://developers.facebook.com>.
2. Kişisel Facebook ile **Get Started**.
3. Telefon numaranı doğrula.
4. Developer politikalarını kabul et.

---

## 3. App oluştur

1. **My Apps** → **Create App**.
2. Use case: **Other** → app type: **Business**.
3. İsim: `Sinapp Social` — iletişim e-postası: kendininki.
4. Oluştur.

---

## 4. Doğru ürünleri ekle

App'in sol menüsü → **Add Product**:

1. **Instagram** → Set up.
2. **Facebook Login for Business** → Set up.

---

## 5. Instagram Graph API'ı yapılandır

**Instagram** ürün ayarları → **API setup with Instagram Business Login**.

| Alan | Değer |
|---|---|
| OAuth Redirect URIs | `https://social.alan-adin.com/api/social-accounts/instagram/callback` |
| Deauthorize Callback URL | `https://social.alan-adin.com/api/social-accounts/instagram/deauthorize` |
| Data Deletion Request URL | `https://social.alan-adin.com/api/social-accounts/instagram/data-deletion` |

Gerekli permission'lar (App Review sırasında talep edeceksin):

- `instagram_basic`
- `instagram_content_publish`
- `instagram_manage_comments`
- `instagram_manage_insights`
- `pages_show_list`
- `pages_read_engagement`
- `business_management`

---

## 6. App ID ve App Secret'ı al

Settings → **Basic**. `.env`'e kopyala:

```ini
INSTAGRAM_APP_ID=<yapıştır>
INSTAGRAM_APP_SECRET=<yapıştır>
INSTAGRAM_CALLBACK_URL=https://social.alan-adin.com/api/social-accounts/instagram/callback
```

`make restart`.

---

## 7. App Review (canlıya çıkmak için zorunlu)

**Development** modundayken yalnızca **Roles** altındaki kişiler (admin, tester, developer) bağlanabilir. Gerçek müşteri kabul etmek için §5'teki her permission'ı **App Review**'e gönder:

1. **App Review** → **Permissions and Features** → her scope'u talep et.
2. Her biri için 60 saniyelik ekran kaydı yükle:
   - Kullanıcı SPA'da "Connect Instagram"a basıyor
   - Facebook login + page seçimi
   - Verinin nasıl kullanıldığı (test caption + görsel post)
3. Meta reviewer'larına test user ver (Business IG'li Facebook hesabı).
4. 5–14 gün bekle.

Onaylandıktan sonra app'i **Live** moduna al.

---

## 8. Post atmak için gerekli altyapı

Bir Instagram post'u yayımlamak için:

1. Görseli **publicly reachable HTTPS URL**'e yükle (Meta server-side fetch eder). Sinapp Laravel'in `public` disk'i + CDN'in ile yapacak — prod için `FILESYSTEM_DISK=s3` ve S3 uyumlu bucket ayarla.
2. Container oluştur: `POST /{ig-user-id}/media`.
3. Yayımla: `POST /{ig-user-id}/media_publish`.

Video ve carousel'lar aynı iki adımlı "container → publish" akışını takip eder.

---

## 9. Uzun ömürlü token'lar

Kısa ömürlü user token'ları 1 saatte ölür. Sinapp:

1. OAuth callback'ten hemen sonra **60 günlük long-lived token**'a takas eder.
2. 50 günde bir scheduled job ile yeniden takas ederek süresini hiç bitirmez.

Kullanıcı `https://accessibility.facebook.com/?tab=apps_and_websites`'tan erişimi iptal ederse token ölür — Sinapp re-connect banner'ı gösterir.

---

## 10. Sık karşılaşılan hatalar

| Hata | Sebep | Çözüm |
|---|---|---|
| `(#10) Application does not have permission` | App hâlâ Dev modunda, kullanıcı tester değil | App Review gönder ya da kullanıcıyı Tester olarak ekle |
| `Invalid OAuth access token` | Token expired/revoked | Kullanıcı yeniden bağlasın |
| `Media URL is not reachable` | Görsel public değil | S3/public disk + CORS kontrol |
| `Page is not Business` | Kullanıcının IG'i Business/Creator değil | Çevirip Page'e bağlamalı |
| `Invalid platform app` | Yanlış App ID/Secret çifti | Settings → Basic'ten tekrar kopyala |
