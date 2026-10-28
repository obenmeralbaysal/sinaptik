# Sinaptik AdsManager — kapsam planı (kodsuz)

Kaynak onay: 877 (Oben, 28 Eki 2026 16:25 TSİ): Social ve Analytics içindeki reklam yönetimleri ayrı ürün olsun.  
Pano: Taskaban #90 (Sinaptik AdsManager), kart #2248.  
Bu belge yalnız plandır. Kod, push, merge ve deploy yok.

## 1. Ürün tanımı

- **Ad:** Sinaptik AdsManager  
- **Alan adı (öneri, onay gerekir):** `ads.sinaptik.com.tr`  
- **Kimlik:** Ortak hesap `auth.sinaptik.com.tr` (Zitadel, OIDC Authorization Code + PKCE). Ayrı OIDC client.  
- **Abonelik:** Ürün kodu `adsmanager` (Sinapp katalog + webhook). Social/Analytics aboneliğinden bağımsız.  
- **İlk kapsam (MVP):** Meta (Facebook/Instagram) reklam hesapları — bugün Social içinde çalışan özellikler.  
- **İkinci kapsam:** Google Ads (Analytics panosundaki #2218–#2224; kod henüz yok, sıfırdan).

## 2. Social’dan taşınacak modüller (kodda var)

Kaynak repo: `obenmeralbaysal/sinapp-social`, referans SHA `4e3a7b2` / `feat/saas-foundation` tabanı.

### Backend
| Alan | Dosya / yol |
|---|---|
| API | `backend/app/Http/Controllers/Api/AdsController.php` |
| Servisler | `backend/app/Services/Ads/AdGuardrailService.php`, `AdProposalService.php` |
| Modeller | `Ad`, `AdAccount`, `AdCampaign`, `AdSet`, `AdInsight`, `AdProposal`, `AdChangeLog` |
| Job | `backend/app/Jobs/SyncAds.php` (+ Meta token/consent ile bağlantılı kısımlar) |
| Rotalar | `/ad-accounts`, `/campaigns`, `/ads/proposals*`, `/ads/changes*`, `/automation-rules*`, `PATCH /workspace/ads-settings` |
| Test | `backend/tests/Feature/AdGuardrailTest.php` (+ InviteAndRole ads izinleri) |
| MCP | `docs/MCP.md` ads scope’ları: `ads:read`, `ads:write`, `ads:budget`; araçlar: list_ad_accounts, list_campaigns, get_ad_insights, propose/apply_optimization, update_budget, pause_resume |
| Meta bağlama (paylaşılan bağımlılık) | `MetaConnectionService`, Graph driver, consent (`ConsentService` / Meta açık rıza) — AdsManager ya kendi Meta bağlantısını tutar ya da Social’daki bağlı varlıkları salt okunur referanslar; **karar gerekir** |

### Frontend
| Alan | Yol |
|---|---|
| Reklamlar UI | `frontend/src/app/uygulama/reklamlar/page.tsx` |
| Workspace ads ayarı | hesaplar/ayarlar içindeki ads-settings parçaları (ayrıştırılacak) |

### Veri
- SaaS domain migration içindeki ads tabloları (`2026_10_17_120000_create_saas_domain.php` ads bölümü) + RLS politikaları.  
- Taşımada: şema kopyası + (kayıt varsa) workspace→account eşlemesi; P0’da Social’da gerçek müşteri kaydı olmadığı varsayımı teyit edilmeli.

## 3. Analytics’ten gelecek kapsam (kod yok; kartlar)

Pano #86 kartları — AdsManager Faz 2:
- #2218 MCC + Ads API geliştirici erişimi  
- #2219 Müşteri reklam hesabı bağlama  
- #2220 Gece veri çekme + GA4 birleştirme  
- #2221 Kural motoru  
- #2222 YZ önerileri + tek tık onay  
- #2223 Sınırlı otomatik mod, değişiklik kaydı, geri alma  
- #2224 Haftalık rapor, uyarılar, MCP  

Not: Analytics iskeletinde Google Ads kodu yok; GA4/Search Console OAuth ve consent var. AdsManager Google Ads’i Analytics’ten “kesip alma” değil, bu kartlara göre yeni yazım.

## 4. Auth / veri paylaşımı (Zitadel)

- Ortak IdP; ürün client’ı `adsmanager`.  
- `users` yansı + `external_subject_id` / `issuer` (Analytics deseni).  
- Hesap (Organization) IdP’de; AdsManager kendi workspace’ini tutar.  
- Sosyal/Analytics ürün verisi (gönderiler, GA4 mülkleri) AdsManager’a kopyalanmaz.  
- Meta/Google reklam token’ları yalnız AdsManager DB’sinde (şifreli).  
- SSO: Social “Reklamlar” menüsü → `ads.sinaptik.com.tr` (sessiz SSO).  
- MFA: owner/admin için zorunlu (809 ile uyumlu).

## 5. Bağımlılıklar

- Zitadel canlı (`auth.sinaptik.com.tr`) — hazır.  
- Sinapp ödeme hub + `product_code=adsmanager` plan kayıtları.  
- Meta uygulama (Sinapp Business app) + `ads_read` / `ads_management` — **833** (yorum izinleri) ve advanced access Oben’de.  
- Google Ads API / MCC — #2218, henüz yok.  
- KVKK / açık rıza metinleri (reklam hesabı bağlama) — taslak `/workspace/sinaptik/icerik/acik-riza-hesap-baglama-TASLAK.md`.  
- Deploy: ayrı compose projesi, mevcut VPS veya onaylı sunucu; yeni public port yok (NPM arkasında).

## 6. Riskler

| Risk | Önlem |
|---|---|
| Social’dan keserken reklam sync’inin kırılması | Önce paralel çalıştır (AdsManager okur/yazar, Social UI yönlendirir); sonra Social ads rotalarını kapat |
| Token’ların iki üründe çoğalması | Tek ürün sahibi kuralı; migrate script tek sefer |
| Meta App Review / izin kapsamı | 833 kapanmadan canlı reklam yazma kapalı kalabilir |
| Guardrail / auto-apply yanlış bütçe | Mevcut AdGuardrail + proposal-first model korunur |
| starter fallback | AdsManager’da abonelik yoksa erişim reddi (Analytics deseni) |
| MCP riskli araçlar | Varsayılan propose; apply yalnız onay + plan feature |

## 7. Fazlar ve süre (bot saati)

| Faz | İçerik | Bot saati |
|---|---|---|
| F0 | Kararlar (domain, Meta token sahibi, Social UI yönlendirme, fiyat) + P0 kayıt teyidi | 1–2 |
| F1 | Repo iskeleti (Social/Analytics yığını), Zitadel client, Sinapp billing `adsmanager`, boş UI kabuğu | 6–8 |
| F2 | Meta ads taşınması (modeller, API, SyncAds, guardrail, proposal, reklamlar UI, testler) | 10–14 |
| F3 | Social’dan ads UI/API deprecations + derin link SSO + veri taşıma (kayıt yoksa atlanır) | 3–5 |
| F4 | Google Ads Faz 2 (#2218–2224 iskelet + bağlama + gece çekme + proposal akışı) | 16–24 |
| F5 | MCP ads araçları, rapor/uyarı, KVKK metin bağlantıları, Test Mühendisi | 4–6 |
| **MVP (F0–F3, yalnız Meta)** | | **20–29** |
| **Tam (MVP + Google Ads F4–F5)** | | **40–59** |

Öneri: önce Meta MVP’yi ayrı üründe canlıya al; Google Ads’i Analytics kartlarıyla AdsManager panosuna taşıyarak ikinci dalga yap.

## 8. Social / Analytics’te reklam kaldırma stratejisi

1. AdsManager MVP hazır → Social `/uygulama/reklamlar` “AdsManager’da aç” yönlendirmesi.  
2. Social ads API’si 1 sürüm deprecated (veya feature flag).  
3. Analytics’te Google Ads kartları (#2218+) AdsManager #90’a taşınır; Analytics SEO/GA4’te kalır.  
4. Kod silme ayrı onay + yedek sonrası.

## 9. Onay bekleyen kararlar

1. Domain: `ads.sinaptik.com.tr` (öneri) mi?  
2. Meta bağlantısı AdsManager’da mı ayrı tutulacak, Social’daki Page/IG bağından mı türeyecek?  
3. MVP yalnız Meta mi, yoksa Google Ads ile birlikte mi?  
4. Fiyat / deneme (Sinapp planları).  
5. Social’dan ads kodunun silinme zamanı (yönlendirme süresi).

## 10. Bu planda yapılmayanlar

Kod yazımı, repo açma, push/merge/deploy, DNS, Meta App Review başvurusu, fiyat kesinleştirme.
