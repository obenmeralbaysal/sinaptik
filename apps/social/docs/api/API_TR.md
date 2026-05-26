# API Referansı (Yeni Başlayanlar İçin) — Sinapp Social

Base URL (prod): `https://social.alan-adin.com` (istekler `/api/...` altına gider)
Base URL (lokal): `http://localhost:3000` (Next.js `/api` isteklerini Laravel'e proxy'ler)

Tüm endpoint'ler `/api` altında.

---

## 0. Önce anlaman gereken kavramlar

### Kimlik doğrulama = Sanctum SPA cookie
Bu, üçüncü partiler için Bearer-token API'ı **değildir** (henüz — `docs/NEXT_PHASES_TR.md`'ye bak). Frontend, `POST /api/login` ile bırakılan session cookie üzerinden konuşur. `curl` ile cookie tutmak için `-c cookies.txt -b cookies.txt` kullan.

### Multi-tenant = `X-Organization-Slug` header
Tenant-scope'lu her endpoint şu header'ı ister:

```
X-Organization-Slug: orgun-slug
```

`ResolveOrganization` middleware'i bu header'ı okur, organizasyonu bulur, üye olup olmadığını kontrol eder ve `OrganizationScope` global scope ile tüm sorguları otomatik filtreler. Header yoksa → `403`.

### Cevap formatı
Tüm kaynaklar JSON döner (Laravel API Resources). Hatalar Laravel standardında:

```json
{ "message": "okunabilir hata", "errors": { "alan": ["..."] } }
```

### Sık HTTP kodları
| Kod | Anlamı |
|---|---|
| `200` | OK |
| `201` | Oluşturuldu |
| `204` | İçerik yok (delete) |
| `401` | Giriş yok |
| `403` | Giriş var ama yetki yok |
| `404` | Kaynak yok veya tenant filtresi dışı |
| `422` | Validation hatası (`errors` objesi alanları söyler) |

---

## 1. Kimlik doğrulama

### `POST /api/register`  *(public)*
Kullanıcı + ilk organizasyon oluşturur.

```bash
curl -c cookies.txt -X POST https://api.social.alan-adin.com/api/register \
  -H 'Content-Type: application/json' \
  -d '{
    "name": "Oben",
    "email": "oben@example.com",
    "password": "secret1234",
    "password_confirmation": "secret1234",
    "organization_name": "Sinaptik"
  }'
```

Cevap `201`:
```json
{ "user": {...}, "organization": { "id": 1, "slug": "sinaptik", "name": "Sinaptik" } }
```

### `POST /api/login`  *(public)*
```bash
curl -c cookies.txt -X POST https://api.social.alan-adin.com/api/login \
  -H 'Content-Type: application/json' \
  -d '{ "email": "oben@example.com", "password": "secret1234" }'
```
Session cookie bırakır. `200` ve user döner.

### `POST /api/logout`  *(auth)*
```bash
curl -b cookies.txt -X POST https://api.social.alan-adin.com/api/logout
```

### `GET /api/me`  *(auth)*
Giriş yapan kullanıcı + organizasyonları + mevcut organizasyonu döner.

### `POST /api/me/switch-organization`  *(auth)*
```bash
curl -b cookies.txt -X POST .../api/me/switch-organization \
  -H 'Content-Type: application/json' \
  -d '{ "organization_id": 2 }'
```

---

## 2. Planlar  *(public)*

### `GET /api/plans`
Kayıt esnasında gösterilecek planlar.

```bash
curl https://api.social.alan-adin.com/api/plans
```

---

## 3. Organizasyon (tenant-self)

`auth:sanctum` + `tenant` ister → `X-Organization-Slug` header'ını gönder.

| Method | Path | Açıklama |
|---|---|---|
| `GET`   | `/api/orgs/{orgSlug}`              | Organizasyonu getir |
| `PATCH` | `/api/orgs/{orgSlug}`              | İsim/slug/ayar güncelle |
| `GET`   | `/api/orgs/{orgSlug}/members`      | Üye listesi |
| `GET`   | `/api/orgs/{orgSlug}/ai-settings`  | AI driver + system prompt |
| `PATCH` | `/api/orgs/{orgSlug}/ai-settings`  | AI driver/prompt/model güncelle |

Örnek:
```bash
curl -b cookies.txt \
  -H 'X-Organization-Slug: sinaptik' \
  https://api.social.alan-adin.com/api/orgs/sinaptik
```

---

## 4. Sosyal hesaplar

| Method | Path | Açıklama |
|---|---|---|
| `GET`    | `/api/social-accounts`           | Bağlı hesaplar |
| `GET`    | `/api/social-accounts/{id}`      | Detay |
| `DELETE` | `/api/social-accounts/{id}`      | Bağlantıyı kes |
| `POST`   | `/api/social-accounts/x/connect` | X OAuth başlat — `auth_url` döner, kullanıcıyı yönlendir |
| `GET`    | `/api/social-accounts/x/callback`| **X kullanıcıyı buraya geri yönlendirir** (sen elle çağırma) |

X bağlama akışı:

```bash
curl -b cookies.txt -X POST \
  -H 'X-Organization-Slug: sinaptik' \
  https://api.social.alan-adin.com/api/social-accounts/x/connect
# → { "auth_url": "https://x.com/i/oauth2/authorize?..." }
```

Tarayıcı `auth_url`'e gider, X geri `/api/social-accounts/x/callback?code=...&state=...` adresine yönlendirir, backend kodu değişip tokenleri şifreli kaydeder ve kullanıcıyı `https://social.alan-adin.com/t/{slug}/accounts?connected=1`'e 302 atar.

---

## 5. Targetlar (yanıt verilecek hesaplar/anahtar kelimeler)

`apiResource('targets')` →

| Method | Path |
|---|---|
| `GET`    | `/api/targets` |
| `POST`   | `/api/targets` |
| `GET`    | `/api/targets/{target}` |
| `PATCH`  | `/api/targets/{target}` |
| `DELETE` | `/api/targets/{target}` |
| `POST`   | `/api/targets/{target}/preview` — AI yanıtını göndermeden önizle |

Oluştur:
```bash
curl -b cookies.txt -X POST \
  -H 'X-Organization-Slug: sinaptik' \
  -H 'Content-Type: application/json' \
  -d '{ "handle": "@laravel", "type": "user", "reply_prompt": "yardımcı ol" }' \
  https://api.social.alan-adin.com/api/targets
```

---

## 6. Postlar

`apiResource('posts')` + ekstra:

| Method | Path | Açıklama |
|---|---|---|
| `POST` | `/api/posts/{post}/send-now`   | Hemen gönder |
| `POST` | `/api/posts/bulk-schedule`     | Toplu zamanla |

Zamanlanmış post:
```bash
curl -b cookies.txt -X POST \
  -H 'X-Organization-Slug: sinaptik' \
  -H 'Content-Type: application/json' \
  -d '{
    "social_account_id": 1,
    "body": "selam dünya",
    "scheduled_at": "2026-06-01T10:00:00Z"
  }' \
  https://api.social.alan-adin.com/api/posts
```

---

## 7. Rutinler (AI ile günlük içerik)

`apiResource('routines')` + ekstra:

| Method | Path | Açıklama |
|---|---|---|
| `POST` | `/api/routines/generate`        | Bugünün partisini şimdi üret |
| `POST` | `/api/routines/regenerate-one`  | Tek slot'u yeniden üret |
| `GET`  | `/api/routines/_meta/models`    | Driver'ın için kullanılabilir AI modelleri |

---

## 8. XML kaynakları (RSS tabanlı postlar)

`apiResource('xml-sources')` + ekstra:

| Method | Path | Açıklama |
|---|---|---|
| `POST` | `/api/xml-sources/{xml_source}/run` | Hemen çek + kuyrukla |

---

## 9. Admin  *(yalnızca super-admin rolü)*

Guard: `can:access-admin`.

| Method | Path | Açıklama |
|---|---|---|
| `GET`    | `/api/admin/stats`        | Sayımlar (org/user/post/ciro) |
| `GET`    | `/api/admin/users`        | Tüm kullanıcılar (paginate) |
| `GET`    | `/api/admin/plans`        | Tüm planlar |
| `POST`   | `/api/admin/plans`        | Plan oluştur |
| `PATCH`  | `/api/admin/plans/{plan}` | Plan güncelle |
| `DELETE` | `/api/admin/plans/{plan}` | Plan sil |

```bash
curl -b cookies.txt https://api.social.alan-adin.com/api/admin/stats
```

---

## 10. Hızlı tarif — uçtan uca mutlu yol

```bash
# 1. kayıt
curl -c jar -X POST .../api/register -H 'Content-Type: application/json' \
  -d '{"name":"O","email":"o@x.io","password":"secret1234","password_confirmation":"secret1234","organization_name":"Acme"}'

# 2. X OAuth başlat (tarayıcı auth_url'i takip edecek)
curl -b jar -X POST -H 'X-Organization-Slug: acme' .../api/social-accounts/x/connect

# 3. target oluştur
curl -b jar -X POST -H 'X-Organization-Slug: acme' -H 'Content-Type: application/json' \
  -d '{"handle":"@laravel","type":"user"}' .../api/targets

# 4. post zamanla
curl -b jar -X POST -H 'X-Organization-Slug: acme' -H 'Content-Type: application/json' \
  -d '{"social_account_id":1,"body":"gm","scheduled_at":"2026-06-01T08:00:00Z"}' .../api/posts
```

Entegrasyonların %95'i bu kadar.
