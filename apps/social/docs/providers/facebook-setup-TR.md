# Facebook (Pages API) Kurulumu — Yeni Başlayanlar İçin

Müşterinin **Facebook Page**'ine post atmak için **Facebook Login + Pages API** ürünlerini içeren bir Meta app gerekir. Aynı Meta app hem Instagram hem Facebook entegrasyonunu host edebilir.

> Sinapp Social'daki durum: env placeholder'ları hazır (`FACEBOOK_APP_ID`, `FACEBOOK_APP_SECRET`), provider implementasyonu **Faz 2** (bkz. `docs/NEXT_PHASES_TR.md`).

---

## 1. Instagram için yaptığın aynı Meta app'i kullan

`docs/providers/instagram-setup-TR.md`'yi izlediysen **ikinci bir app gerekmez**. Sadece Facebook Login ürününü ekle. Atlamış olabilirsin — o dokümandaki 1-4. adımları yap, geri dön.

---

## 2. Facebook Login ekle

1. App dashboard → **Add Product** → **Facebook Login** → Set up.
2. **Web** seç.
3. Site URL: `https://social.alan-adin.com`.

**Facebook Login → Settings**:

| Alan | Değer |
|---|---|
| Client OAuth Login | ✅ Yes |
| Web OAuth Login | ✅ Yes |
| Use Strict Mode for redirect URIs | ✅ Yes |
| Valid OAuth Redirect URIs | `https://api.social.alan-adin.com/api/social-accounts/facebook/callback` |

---

## 3. Talep edeceğin izinler

| Permission | Amaç |
|---|---|
| `email`                              | Hesap kimliği |
| `public_profile`                     | İsim + avatar |
| `pages_show_list`                    | Kullanıcının Page'lerini listele |
| `pages_read_engagement`              | Post metriklerini oku |
| `pages_manage_posts`                 | **Page adına post yayımla** |
| `pages_manage_metadata`              | Webhook abone et |
| `pages_messaging` *(inbox eklersen)* | DM'lere yanıt |
| `business_management`                | Business Manager erişimi |

`pages_manage_posts` **App Review** + Meta business verification ister.

---

## 4. App kimlik bilgileri → `.env`

Settings → **Basic**:

```ini
FACEBOOK_APP_ID=<yapıştır>
FACEBOOK_APP_SECRET=<yapıştır>
FACEBOOK_CALLBACK_URL=https://api.social.alan-adin.com/api/social-accounts/facebook/callback
```

`make restart`.

---

## 5. Page Access Token modeli (önemli!)

OAuth sana **User Access Token** verir. Onunla post atmazsın. Akış:

1. Kullanıcı Facebook OAuth'a girer → backend user token alır.
2. Backend `GET /me/accounts` çağırır → kullanıcının Page'lerini **her biri kendi Page Access Token**'ı ile döner.
3. Sinapp seçilen Page'in `page_id` + `page_access_token`'ını (şifreli) `social_accounts`'a kaydeder.
4. Tüm post çağrıları **Page token** kullanır, user token değil: `POST /{page-id}/feed`.

Long-lived user token ile alınan Page token'lar, kullanıcı erişimi iptal etmedikçe veya şifre değiştirmedikçe **süresiz**dir.

---

## 6. App Review — reviewer'ların istedikleri

`pages_manage_posts` için:

1. 60 saniyelik ekran kaydı: user login → Page seç → Sinapp'ten test post.
2. Reviewer credentials: en az bir Page'ine admin olduğu bir Facebook hesabı.
3. Business verification: **Business Settings**'te şirket belgelerini yükle. ~3 iş günü.
4. Token'ı nasıl sakladığın (şifreli) ve ne zaman sildiğin (disconnect / son login sonrası 30 gün) detaylı açıkla.

---

## 7. Webhooks (opsiyonel ama tavsiye edilir)

Polling yerine real-time bildirim (yeni yorum, mention) almak için:

1. App → **Webhooks** → **Page** objesi.
2. Callback URL: `https://api.social.alan-adin.com/api/webhooks/facebook`
3. Verify token: rastgele string, `.env`'e `FACEBOOK_WEBHOOK_VERIFY_TOKEN` olarak ekle (Faz 2).
4. `feed`, `mention`, `messages`'a subscribe ol.

---

## 8. Sık karşılaşılan hatalar

| Hata | Sebep | Çözüm |
|---|---|---|
| `(#200) Permissions error` | Page için `pages_manage_posts` eksik | OAuth'u tekrar yap, Page'i açıkça seç |
| `(#190) Invalid OAuth access token` | Token revoke edilmiş / şifre değişmiş | Kullanıcı yeniden bağlasın |
| `URL blocked: This redirect failed` | Redirect URI Valid OAuth listesinde yok | Facebook Login settings'e tam URL'i ekle |
| `App Not Set Up: This app is still in development mode` | App Dev modunda, kullanıcı tester değil | Live moduna geç + App Review'ı bitir |
| `(#100) Tried accessing nonexisting field` | Yanlış Graph API versiyonu | Servis çağrılarında `v19.0` veya üzeri kullan |
| `Business verification required` | `pages_manage_posts` için business doğrulanmamış | Meta Business Suite'te şirket belgeleri yükle |

---

## 9. Müşterinin Page'inde gereken rol

API ile Page'e post atmak için bağlanan kullanıcı o Page'de en az **Editor** olmalı (en iyisi **Admin**). Sadece **Moderator** veya **Advertiser**'sa post sessizce başarısız olur.
