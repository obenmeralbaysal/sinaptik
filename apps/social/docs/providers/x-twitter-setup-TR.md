# X (Twitter) Developer Hesabı Kurulumu — Yeni Başlayanlar İçin

Kullanıcıların X hesaplarını Sinapp Social'a bağlayabilmesi için **OAuth 2.0 + PKCE** açık bir X developer uygulamasına ihtiyacın var.

---

## 1. Developer hesabı aç

1. <https://developer.x.com/en/portal/dashboard> adresine git.
2. Uygulamayı SAHİPLENECEK X hesabıyla giriş yap (kişisel değil, şirket hesabı tercih et).
3. **Sign up for Free Account**'a tıkla (test için Free yeterli, daha fazla rate limit için Basic/Pro).
4. Kullanım amacı formunu doldur. 1 paragraf dürüst cevap yeterli:
   > "İşletmelere post zamanlama ve mention'lara yanıt verme imkanı sunan bir SaaS geliştiriyoruz. Yalnızca OAuth 2.0 ile hesabını bağlayan kullanıcılar adına işlem yapıyoruz."
5. E-posta doğrulama istenirse onayla.

---

## 2. Project + App oluştur

1. Portal'da → **Projects & Apps** → **Add Project**.
   - İsim: `Sinapp Social`
   - Use case: `Building tools for businesses`
2. Projenin içinde → **Add App** → `Sinapp Social Prod` koy (dev için ayrı bir tane daha açacaksın).

---

## 3. OAuth 2.0'ı aç (en kritik adım)

App settings → **User authentication settings** → **Set up**.

| Alan | Değer |
|---|---|
| App permissions | **Read and write** |
| Type of App | **Web App, Automated App or Bot** |
| **OAuth 2.0** | ✅ açık |
| OAuth 1.0a | ❌ kapalı bırak (kullanılmıyor) |
| Callback URI / Redirect URL | `https://social.alan-adin.com/api/social-accounts/x/callback` |
| Website URL | `https://social.alan-adin.com` |
| Terms of service / Privacy policy URL | URL'lerin (prod için zorunlu) |

**Save**'e bas. Client ID ve Client Secret **TEK SEFER** gösterilir — şimdi `.env`'e kopyala:

```ini
X_CLIENT_ID=<yapıştır>
X_CLIENT_SECRET=<yapıştır>
X_CALLBACK_URL=https://social.alan-adin.com/api/social-accounts/x/callback
```

Backend'i yeniden başlat:
```bash
make restart
```

---

## 4. Sinapp'in kullandığı scope'lar

Kullanıcıdan istenenler:

| Scope | Neden |
|---|---|
| `tweet.read`     | Tweet ve mention'ları okumak |
| `tweet.write`    | Zamanlanmış tweet atmak |
| `users.read`     | Profil bilgisi |
| `offline.access` | Refresh token alıp erişimi kaybetmemek |

Bunlar `App\Services\X\XOAuthService` içinde sabit — X portalında ayrıca girmene gerek yok.

---

## 5. Bağlantıyı test et

1. `https://social.alan-adin.com`'a giriş yap, tenant'ına geç.
2. **Accounts** → **Connect X account**.
3. `x.com/i/oauth2/authorize?...` sayfasına gidersin → **Authorize app**.
4. Geri yönlendirilirsin. Handle + avatar listede görünmeli.

---

## 6. Rate limit'ler

| Tier | Post / ay | Ücret |
|---|---|---|
| Free   | 1.500 / kullanıcı / ay | $0 |
| Basic  | 50.000 / ay            | $200 |
| Pro    | 1.000.000 / ay         | $5.000 |

Scheduler rate limit'e duyarlı ama birden fazla tenant'la Free'de hızla biter.

---

## 7. Sık karşılaşılan hatalar

| Hata | Sebep | Çözüm |
|---|---|---|
| `redirect_uri does not match` | `X_CALLBACK_URL` portaldakinden farklı | Birebir aynı yap (https, sondaki / yok) |
| `Unauthorized client` | OAuth 2.0 açık değil | Adım 3'ü tekrar yap, OAuth 2.0 toggle'ı ON olsun |
| `invalid_scope` | v2 scope'u olmayan eski app | App'i sıfırdan kur |
| `Could not authenticate you` | `.env`'deki Client Secret yanlış | Portal'dan secret'ı regenerate et, `.env`'i güncelle, `make restart` |
| Token 2 saat sonra eskidi, auto-refresh yok | `offline.access` scope yok | Disconnect + reconnect; Sinapp her zaman ister |

---

## 8. Prodüksiyon checklist

- [ ] App, Development değil **Production** ortamında
- [ ] Privacy policy + ToS URL'leri dolu (X bunu inceler)
- [ ] **İki ayrı app** var: biri `social.alan-adin.com` (prod), diğeri `localhost` / staging
- [ ] Sunucudaki `.env`'de `APP_ENV=production` ve `APP_DEBUG=false`
- [ ] `X_CALLBACK_URL` `https://` ile başlasın, `http://` değil
