# Sinapp Social — Yol Haritası / Sonraki Fazlar

Faz 1 (tamam): X (Twitter) provider, AI (Gemini/OpenAI), rutinler, XML kaynakları, multi-tenant SPA, admin, Sanctum SPA auth, Horizon, nginx-proxy-manager arkasında Docker'lı deployment.

Aşağıda sıralı backlog var. Her faz 1-3 haftalık iş.

---

## Faz 2 — Instagram & Facebook provider'ları

**Hedef:** müşteriler IG Business + FB Page hesaplarını bağlayıp, X gibi post zamanlayabilsin.

- `App\Services\Instagram\InstagramClient` + `InstagramOAuthService` (`App\Contracts\SocialProvider`'a uygun).
- `App\Services\Facebook\FacebookClient` + `FacebookOAuthService`.
- `SocialAccountController`'a `instagramConnect/instagramCallback`, `facebookConnect/facebookCallback` metodları.
- FB OAuth sonrası Page seçim ekranı (bir user, N Page → birini seç).
- Prod'da `FILESYSTEM_DISK=s3` (veya DO Spaces / Cloudflare R2) — IG medya URL'i çekebilsin.
- Uzun ömürlü token refresh job'u (her 50 günde).
- Frontend: `/t/{slug}/accounts`'ta provider sekmeleri, generic `<ConnectProviderButton provider="instagram">` component'i.
- Test: PKCE/OAuth happy path + token expiry.

Bkz: `docs/providers/instagram-setup-TR.md`, `docs/providers/facebook-setup-TR.md`.

---

## Faz 3 — Ödeme (gerçek faturalama)

**Hedef:** `NullGateway`'i gerçek ciroya çevir.

- TR pazarı için `App\Services\Payments\PaytrGateway` (3D Secure, hosted page).
- Uluslararası için `App\Services\Payments\StripeGateway`.
- `PAYMENT_GATEWAY` env'ine göre `AppServiceProvider`'da doğru gateway'i bind et.
- Planlar admin'de zaten var (`/api/admin/plans`). Eklenecek:
  - `Organization` üzerinde subscription lifecycle'ı (trialing / active / past_due / canceled).
  - Her iki gateway için webhook endpoint'i.
  - Kota uygulama middleware'i (plan limitine karşı post sayısı).
- Frontend: `/t/{slug}/billing` sayfası, plan picker, fatura listesi, kota dolduğunda "upgrade" CTA'ları.

---

## Faz 4 — Çoklu üyeli tenant (takım modu)

**Hedef:** bir organizasyon takım arkadaşlarını rollerle davet edebilsin.

- `organization_user` pivot'unda `role` kolonu var. Davet akışı:
  - `POST /api/orgs/{slug}/invitations` (yalnız admin)
  - İmzalı e-posta + accept link
  - `POST /api/invitations/{token}/accept` (auth gerekli, org'a katılır)
- spatie/laravel-permission ile roller: `owner`, `admin`, `editor`, `viewer`.
- Resource başı policy (örn. yalnız `admin+` sosyal hesap disconnect edebilir).
- Frontend: `/t/{slug}/settings/team` sayfası (davet, rol değiştir, çıkar).

---

## Faz 5 — Prod mail + Analytics

- Mail: prod'da `MAIL_MAILER=resend` veya `sendgrid`, transactional template'ler (welcome, davet, parola sıfırlama, haftalık rapor).
- Analytics ingestion:
  - Her X/IG/FB post gönderilince +1s, +24s, +7g'de metric fetch zamanla.
  - `post_metrics` tablosunda snapshot'lar (impression, like, reply, repost).
- Frontend: `/t/{slug}/analytics` dashboard, line chart'lar (Recharts), per-post drill-down, CSV export.

---

## Faz 6 — Yeni provider'lar

TR pazar talebine göre öncelik sırası:

1. **LinkedIn** (Marketing Developer Platform — onay süreci uzun, erken başla).
2. **TikTok for Business** (Content Posting API).
3. **Threads** (Meta — kolay, FB app'in üstüne biner).
4. **YouTube Shorts** (Google Cloud OAuth + YouTube Data API v3, app verification ister).

Her biri Faz 2'de oturan `SocialProvider` contract + `OAuthService` pattern'ini izler.

---

## Faz 7 — AI geliştirmeleri

- Görsel üretimi: mevcut `AIDriver` contract'ına `generateImage()` metodu ekleyip DALL-E 3 / Google Imagen entegre et.
- Video post: kısa form dikey video üretimi (Runway / Sora API GA olunca), IG Reels + TikTok + Shorts'a zamanla.
- Zamanlama asistanı: Faz 5'teki geçmiş metriklere göre hesap başına en iyi post saatlerini AI öner.
- Marka sesi & yönergeler: her organizasyon için tone/voice config, prompt'lara prepend (`ai-settings`'te kısmen var).

---

## Faz 8 — Public API + White-label

- **Public API**: aynı endpoint'leri `/v1/` altında Bearer-token auth ile aç (Sanctum personal access tokens). Token başı rate-limit. `darkaonline/l5-swagger` ile auto-generated OpenAPI spec.
- **White-label / reseller**: ajanslar logo, primary color, login ekranı, custom domain (CNAME → frontend) özelleştirebilsin. `Brand` modeli + SPA'ya branding enjekte eden middleware.
- Sinapp webhook'larıyla tetiklenen Zapier / n8n entegrasyonu (`post.published`, `reply.sent`, `quota.warning`).

---

## Faz 9 — Sertleştirme

- Postgres için çok-bölgeli read replica.
- Yedekleme otomasyonu + restore tatbikatı (postgres + S3).
- Backend + frontend hata takibi için Sentry.
- NPM / Cloudflare'de rate-limit + WAF kuralları.
- SOC 2 hazırlığı: privileged action başına audit log tablosu, veri saklama politika dokümanı, GDPR/KVKK veri-export endpoint'i.

---

## Bilerek roadmap'e koymadıklarımız

- Native mobil app'ler (v1-v3 için PWA yeterli).
- Doğrudan reklam kampanya yönetimi (Meta Ads API, X Ads API) — kapsam dışı; ileride Adscanner tarzı read-only entegre edilebilir.
- Her provider'ın garipliklerini tam anlamadan "tek input, çok ağ" sihirli cross-poster.
