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

İlk migrasyon konteyner açılışında çalışır. Plan satırları:

```bash
make seed
```

## Kimlik

Tüm Sinaptik ürünleri ileride `auth.sinaptik.com.tr` üzerinde ortak OIDC hesabı kullanacak. Giriş `App\Contracts\IdentityProvider`, abonelik `App\Contracts\SubscriptionProvider` arkasındadır.

`IDENTITY_DRIVER=local` parola ile giriş yapar ve `external_subject_id` üretir. `users` tablosu asıl hesap değildir; `issuer` + `external_subject_id` yerel yansıdır, e-posta ve ad önbellektir. `OidcIdentityProvider` bilinçli olarak boştur.

## Abonelik

Aktif abonelik yoksa erişim reddedilir; varsayılan plana düşülmez. Katalog `GET /api/sinapp/plans`. Olaylar `POST /api/webhooks/sinapp-subscriptions` (HMAC, `SINAPP_WEBHOOK_SECRET`). Site oluşturma `LimitGuard` ile plan limitine bakar.

## Test

```bash
make test PHP=/opt/homebrew/opt/php@8.3/bin/php
cd frontend && npm run typecheck && npm run lint
```

Docker yoksa PHPUnit sqlite bellek veritabanını kullanır.
