# OIDC (Zitadel)

`IDENTITY_DRIVER=local` parola kaydı ve girişi açık tutar. `IDENTITY_DRIVER=oidc` parola kaydı, girişi ve şifre sıfırlamayı kapatır (HTTP 501). Tarayıcı şu yolları kullanır:

- `GET /auth/login` — Authorization Code + PKCE (S256), sonra kimlik sağlayıcıya yönlenir
- `GET /auth/callback` — `id_token` JWKS ile doğrulanır, kullanıcı `sub` + `iss` ile eşlenir

Nginx `/auth/` isteklerini PHP-FPM'e verir. Next.js bu yolları barındırmaz. `/giris` ve `/kayit`, sürücü `oidc` ise `GET /api/auth/identity` yanıtındaki `login_url` adresine gider.

Üretim hedefi: `OIDC_ISSUER=https://auth.sinaptik.com.tr`. Yönlendirme: `OIDC_REDIRECT_URI=https://social.sinaptik.com.tr/auth/callback`. Anahtar adları `.env.example` içindedir. İstemci gizlisi depoya yazılmaz.

İlk başarılı giriş, doğrulanmış e-posta ile bir çalışma alanı açar. E-posta başka bir yerel kullanıcıdaysa eşleme reddedilir. `amr` oturumda saklanır. Sahip için yerel TOTP kapısı (`require_owner_2fa`) OIDC `amr` ile değiştirilmez.
