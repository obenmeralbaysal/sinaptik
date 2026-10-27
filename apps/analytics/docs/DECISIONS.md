# Kararlar

Kısa ADR listesi. Tarih: 2026-10-27.

## ADR-001 — Kimlik: Zitadel, Authorization Code + PKCE

Ortak hesap `auth.sinaptik.com.tr` üzerindeki Zitadel olacak. Bu uygulama OIDC Authorization Code ve PKCE (S256) konuşur. `GET /auth/login` yönlendirir; `state`, `nonce` ve `code_verifier` sunucu oturumundadır. `GET /auth/callback` kodu jeton uç noktasında değiştirir. `id_token` issuer discovery belgesindeki JWKS ile doğrulanır (RS256, `iss`, `aud`, `exp`, `nonce`; varsa `c_hash`). `sub` yerel `external_subject_id`, `iss` yerel `issuer` olur. `users` satırı hesap değildir; yansıdır. Jeton ve istemci sırrı loglanmaz, tarayıcıya geri yazılmaz.

## ADR-002 — Hesap ortak, abonelik ürün başına

Kimlik tüm Sinaptik ürünlerinde aynı OIDC öznesidir. Abonelik satırı `product_code` taşır. Bu uygulamanın `SubscriptionProvider` implementasyonu yalnız `analytics` kodunu aktif plan sayar. Katalog belgesindeki `product` alanı `sinaptik-analytics` olarak kalır; o alan kamu plan listesinin adıdır, abonelik kapsamı değildir. Olayda `product_code` yoksa mevcut satırın kodu korunur; yeni satırda varsayılan `analytics`'tir.

## ADR-003 — Owner ve admin için MFA

Owner veya admin rolüyle yapılan çalışma alanı işlemleri, oturumdaki `amr` içinde `mfa` veya `otp` yoksa `403` / `mfa_required` döner. Üye rolü bu kontrole takılmaz. Değer `id_token` içindeki `amr` claim'inden gelir. Yerel sürücüde OIDC claim'i yoktur; `IDENTITY_LOCAL_MFA` açıksa yerel oturum koşulu sağlar, kapalıysa sağlamaz. OIDC sürücüsünde bu bayrak yok sayılır.

## ADR-004 — Sosyal giriş yalnız Google

Google, Zitadel üzerinde tanımlı tek sosyal IdP'dir. Uygulama Google'a doğrudan yetkilendirme kodu ile giriş yapmaz. `GOOGLE_CLIENT_*` Search Console ve GA4 içindir. `OIDC_IDP_HINT` doluysa authorize isteğine Zitadel'in `idps` parametresi olarak eklenir; boşsa eklenmez.

## ADR-005 — Yerel Zitadel

Geliştirme IdP'si `docker compose --profile sso` ile açılır ve varsayılan `up` hedefine girmez. İmaj `ghcr.io/zitadel/zitadel:v2.66.4` (tek konteyner, Login V1). Veritabanı, mevcut Postgres içinde ayrı `zitadel` veritabanıdır. Yayın yalnız `127.0.0.1` üzerindedir. Masterkey, veritabanı parolası ve ilk yönetici parolası repoda yoktur; `.env.example` boş yer tutar. Bu konteyner üretim issuer'ı değildir.

## ADR-006 — Hukuk metinleri yerel taslak

`docs/legal/` altındaki KVKK aydınlatma ve gizlilik metinleri yayımlanmamıştır. Başlıkları `TASLAK – yayınlanmadı`dır. Bilinmeyen adres ve iletişim `[DOLDURULACAK]` bırakılır. Ürün arayüzünden bu dosyalara bağlantı verilmez.
