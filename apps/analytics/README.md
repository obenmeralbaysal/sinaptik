# Sinaptik Analytics

SEO, GEO ve site analitiği için Laravel 11 + Next.js 15 iskeleti. Ürün adresi `analytics.sinaptik.com.tr`.

## Yerel çalıştırma

PHP 8.3 ve Docker gerekir. Yayın portları yalnız `127.0.0.1` üzerindedir ve Social (`…11`) portlarıyla çakışmaz.

```bash
cp .env.example .env
```

`.env` içinde boş bırakılan `APP_KEY`, `DB_PASSWORD` ve `REDIS_PASSWORD` alanlarını doldurun. Anahtar üretmek için:

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

Ardından:

```bash
docker compose up -d --build
```

| Servis | Adres |
| --- | --- |
| Arayüz | http://127.0.0.1:3027 |
| API | http://127.0.0.1:8027 |
| PostgreSQL 16 | 127.0.0.1:5427 |
| Redis 7 | 127.0.0.1:6327 |
| MailHog SMTP / arayüz | 127.0.0.1:1027 / 127.0.0.1:8227 |
| Zitadel (yalnız `sso` profili) | http://127.0.0.1:8127 |

İlk migrasyon konteyner açılışında çalışır. Plan satırları:

```bash
make seed
```

## Kimlik

Tüm Sinaptik ürünleri ileride `auth.sinaptik.com.tr` üzerinde ortak OIDC hesabı kullanacak. Giriş `App\Contracts\IdentityProvider`, abonelik `App\Contracts\SubscriptionProvider` arkasındadır.

`IDENTITY_DRIVER=local` parola ile giriş yapar ve `external_subject_id` üretir. `users` tablosu asıl hesap değildir; `issuer` + `external_subject_id` yerel yansıdır, e-posta ve ad önbellektir.

`IDENTITY_DRIVER=oidc` Authorization Code + PKCE (S256) kullanır. `GET /auth/login` sağlayıcıya yönlendirir; `state`, `nonce` ve `code_verifier` oturumda durur. `GET /auth/callback` kodu değiştirir, `id_token` imzasını issuer discovery üzerinden JWKS ile doğrular. `sub` → `external_subject_id`, `iss` → `issuer`.

Owner ve admin rolündeki çalışma alanı işlemleri `amr` içinde `mfa` veya `otp` ister (`RequireMfaForPrivileged`). Yerel sürücüde bu kontrol `IDENTITY_LOCAL_MFA` ile açılıp kapanır; OIDC sürücüsünde bayrak yok sayılır.

Sosyal giriş yalnız Google'dır ve Zitadel konsolunda IdP olarak tanımlanır. Uygulama Google'a doğrudan giriş yapmaz. `GOOGLE_CLIENT_*` Search Console / GA4 içindir. İsteğe bağlı `OIDC_IDP_HINT`, Zitadel'deki Google IdP kimliğini authorize isteğinin `idps` parametresine yazar; boşsa parametre gitmez.

## Yerel Zitadel

Bu profil yalnız geliştirme içindir. Üretim kimliği `auth.sinaptik.com.tr` üzerindeki Zitadel'dir; buradaki konteyner onun yerine geçmez. `docker compose up` Zitadel'i açmaz.

İmaj `ghcr.io/zitadel/zitadel:v2.66.4` (tek konteyner, Login V1). Ayrı bir veritabanı kullanır: mevcut Postgres içinde `zitadel`. Yayın adresi yalnız `127.0.0.1:8127`.

1. `.env` içinde boş duran `ZITADEL_MASTERKEY` (tam 32 karakter), `ZITADEL_DB_PASSWORD`, `ZITADEL_ADMIN_USERNAME` ve `ZITADEL_ADMIN_PASSWORD` alanlarını doldurun. Parola en az 8 karakter olsun; büyük harf, küçük harf, rakam ve sembol içersin. Anahtarı üretip yalnız `.env` dosyasına yazın:

```bash
php -r "echo substr(str_replace(['+','/','='], '', base64_encode(random_bytes(24))), 0, 32), PHP_EOL;"
```

2. Postgres ayaktayken:

```bash
make up-sso
```

İlk açılışta Zitadel, mevcut Postgres süper kullanıcısı (`DB_USERNAME` / `DB_PASSWORD`) ile `zitadel` veritabanını ve `zitadel` rolünü oluşturur. Rol parolası `ZITADEL_DB_PASSWORD` değeridir. Masterkey ilk kurulumdan sonra değiştirilemez. Veritabanı oluşmazsa, parola yazmadan:

```bash
docker compose exec postgres sh -c 'psql -U "$POSTGRES_USER" -d postgres -c "CREATE DATABASE zitadel;"'
```

3. Konsol: http://127.0.0.1:8127/ui/console — tarayıcıda `localhost` değil `127.0.0.1` kullanın. İlk kullanıcı `.env` içindeki yönetici adıdır.

4. Projede bir uygulama açın: OIDC, Authorization Code, PKCE. Yönlendirme adresi `http://127.0.0.1:8027/auth/callback` (veya `OIDC_REDIRECT_URI`).

5. Google IdP'sini Zitadel'e ekleyin ve başka sosyal sağlayıcı tanımlamayın. Kimliği biliyorsanız `OIDC_IDP_HINT` alanına yazın.

6. `.env`:

```
IDENTITY_DRIVER=oidc
OIDC_ISSUER=http://127.0.0.1:8127
OIDC_CLIENT_ID=<konsoldaki istemci kimliği>
OIDC_CLIENT_SECRET=<yalnızca .env>
```

Owner/admin için Zitadel'de MFA'yı zorunlu yapın. `amr` claim'inde `mfa` veya `otp` yoksa ilgili API çağrıları reddedilir.

## Abonelik

Aktif abonelik yoksa erişim reddedilir; varsayılan plana düşülmez. Katalog `GET /api/sinapp/plans`. Olaylar `POST /api/webhooks/sinapp-subscriptions` (HMAC, `SINAPP_WEBHOOK_SECRET`). Site oluşturma `LimitGuard` ile plan limitine bakar.

## Test

```bash
make test PHP=/opt/homebrew/opt/php@8.3/bin/php
cd frontend && npm run typecheck && npm run lint
```

Docker yoksa PHPUnit sqlite bellek veritabanını kullanır.
