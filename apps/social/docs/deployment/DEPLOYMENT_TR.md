# Sıfırdan Deployment Rehberi — Sinapp Social

Bu rehber, üzerinde **nginx-proxy-manager (NPM)** kurulu bomboş bir Ubuntu sunucuyu alıp uygulamayı 30 dakikada online'a çıkarmanı sağlar. Docker bilmen gerekmez.

---

## 1. Başlamadan önce gerekli olanlar

| Ne | Niye |
|---|---|
| Ubuntu 22.04+ bir VPS (en az 2 vCPU / 4 GB RAM) | Docker container'lar çalışsın |
| `docker` + `docker compose` plugin'i kurulu | Stack'i çalıştırmak için |
| `nginx-proxy-manager` zaten çalışıyor (genelde 80/443/81) | HTTPS + reverse proxy |
| Bir DNS A kaydı sunucunun IP'sine: `social.alan-adin.com` | Dışarıdan erişim |
| Bir X (Twitter) developer uygulaması — bkz. `docs/providers/x-twitter-setup-TR.md` | OAuth login |
| (Opsiyonel) Gemini veya OpenAI API anahtarı | AI ile post üretimi |

Docker'ı tek seferlik kurulum:

```bash
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER
# çıkış yap, tekrar giriş yap
```

---

## 2. Repoyu klonla

```bash
cd /opt
git clone https://github.com/your-org/sinapp-social.git
cd sinapp-social
```

---

## 3. Tek bir `.env` dosyasını yapılandır

Repo kökünde **tek** bir `.env` var. Her şey (backend, frontend, postgres, redis, mailhog) bu dosyayı okur.

```bash
cp .env.example .env
nano .env
```

### MUTLAKA değiştirmen gereken değişkenler

| Değişken | Ne yazacaksın | Örnek |
|---|---|---|
| `APP_URL` | Uygulamanın tam HTTPS URL'i (API `/api` altında) | `https://social.alan-adin.com` |
| `FRONTEND_URL` | Aynı — tek domain | `https://social.alan-adin.com` |
| `APP_DOMAIN` | Hostname (sade) | `social.alan-adin.com` |
| `DB_PASSWORD` | Güçlü rastgele şifre | `openssl rand -hex 24` |
| `REDIS_PASSWORD` | Güçlü rastgele şifre | `openssl rand -hex 24` |
| `SESSION_DOMAIN` | Başında nokta + ana alan | `.alan-adin.com` |
| `SANCTUM_STATEFUL_DOMAINS` | Frontend host'u | `social.alan-adin.com` |
| `X_CLIENT_ID` / `X_CLIENT_SECRET` | X developer portalından | — |
| `X_CALLBACK_URL` | X portalındakiyle BİREBİR aynı olmalı | `https://social.alan-adin.com/api/social-accounts/x/callback` |
| `AI_DRIVER` + anahtar | `gemini` veya `openai` | + `GEMINI_API_KEY=...` |
| `NEXT_PUBLIC_API_URL` | Boş bırak (same-origin `/api`) | *(boş)* |
| `NEXT_PUBLIC_APP_URL` | `FRONTEND_URL` ile aynı | `https://social.alan-adin.com` |
| `API_PROXY_TARGET` | Docker içi backend (değiştirme) | `http://backend:80` |

### Host portları (çakışma yoksa değiştirme)

Tüm host portları `11` ile biter — böylece NPM her zaman tahmin edilebilir bir aralığa proxy yapar:

| Servis | Host port | Container port |
|---|---|---|
| Frontend (Next.js) | `3011` | 3000 |
| Backend (Laravel) | `8011` | 80 |
| PostgreSQL | `5411` | 5432 |
| Redis | `6311` | 6379 |
| MailHog SMTP | `1011` | 1025 |
| MailHog UI | `8211` | 8025 |

`APP_KEY`'i boş bırak — `make deploy` senin için üretecek.

---

## 4. Tek komutla deploy

```bash
make deploy
```

Bu komut sırasıyla:
1. `git pull --ff-only` ile güncel kodu çeker
2. backend, horizon, scheduler ve frontend docker imajlarını build eder
3. postgres + redis'i başlatır ve healthy olmalarını bekler
4. Yoksa `APP_KEY` üretir
5. `php artisan migrate --force --seed` çalıştırır
6. Bütün servisleri ayağa kaldırır
7. config / route / view cache'lerini oluşturur ve storage symlink atar
8. Migration ve seed'i tekrar uygular, bekleyen migration kalmadığını ve `starter` / `pro` / `agency` planlarının seed edildiğini doğrular (başarısızsa komut durur)

Bittiğinde 6 container'ın `Up` durumda olmalı:

```bash
make ps
```

---

## 5. nginx-proxy-manager yapılandırması

NPM'i aç (genelde `http://sunucun:81`) ve **tek bir Proxy Host** ekle. API istekleri `/api` ve `/sanctum` alt yollarından backend'e gider.

### Proxy host — frontend + API (subfolder)

| Alan | Değer |
|---|---|
| Domain Names | `social.alan-adin.com` |
| Scheme | `http` |
| Forward Hostname / IP | `127.0.0.1` (veya docker host'un LAN IP'si) |
| Forward Port | `3011` |
| Cache Assets | ✅ |
| Block Common Exploits | ✅ |
| Websockets Support | ✅ |
| SSL → Request a new Let's Encrypt certificate | ✅ |
| Force SSL + HTTP/2 | ✅ |

**Custom Locations** sekmesinde iki konum ekle (sıra önemli — `/api` ve `/sanctum` ana proxy'den önce eşleşmeli):

| Location | Forward Hostname | Forward Port |
|---|---|---|
| `/api` | `127.0.0.1` | `8011` |
| `/sanctum` | `127.0.0.1` | `8011` |

Ana proxy'nin **Advanced** sekmesine şunu yapıştır — dosya yüklemeler ve uzun süren AI üretimleri timeout vermesin:

```nginx
client_max_body_size 50M;
proxy_read_timeout 300;
proxy_send_timeout 300;
```

**Save**'e bas. Sertifikanın gelmesi ~30 saniye.

---

## 6. Hızlı test

```bash
curl -I https://social.alan-adin.com/api/plans
# 200 OK
```

`https://social.alan-adin.com` adresini aç, kullanıcı kaydı yap, `/t/{slug-in}/dashboard` sayfasına düşmelisin.

---

## 7. Günlük operasyon komutları

```bash
make update          # git pull + rebuild + migrate (sıfır müdahale güncelleme)
make logs            # tüm log'lar
make ps              # durum
make restart         # tüm container'ları yeniden başlat
make shell-backend   # Laravel container'ına bash
make pg-shell        # psql
make horizon-status  # kuyruk sağlığı
make fresh           # YIKICI: DB'yi siler, baştan seed eder
```

---

## 8. Yedekleme

Postgres verisi `pgdata` named volume'unda durur.

```bash
docker run --rm -v sinapp-social_pgdata:/data -v $PWD:/backup alpine \
    tar czf /backup/pgdata-$(date +%F).tar.gz -C /data .
```

Bunu bir cron job'a bağla.

---

## 9. Sorun giderme

| Belirti | Olası sebep | Çözüm |
|---|---|---|
| NPM `502 Bad Gateway` | Container kapalı veya yanlış port | `make ps`, sonra `make logs` |
| Tarayıcı konsolunda `CORS error` | `SANCTUM_STATEFUL_DOMAINS` / `SESSION_DOMAIN` uyuşmazlığı | `.env`'i düzelt, `make restart` |
| Login başarılı ama `/me` 401 | Cookie domain'i yanlış | `SESSION_DOMAIN=.alan-adin.com` (başında nokta) |
| `/api/*` 404 (Next.js sayfası) | NPM Custom Location eksik | `/api` ve `/sanctum` → port `8011` ekle |
| X OAuth: `redirect_uri mismatch` | `X_CALLBACK_URL` X portalından farklı | Birebir aynı olmalı |
| Horizon iş görmüyor | Worker çalışmıyor | `make ps` (horizon Up olmalı), `make logs` |
| `php artisan: APP_KEY missing` | Adım atlanmış | `make shell-backend` → `php artisan key:generate --force` |
| Frontend CSS yok / ham metin | `output: 'standalone'` build eksik | `make build-frontend && make restart` |
| `pg_isready` yeşil olmuyor | Eski volume ile `.env`'deki `DB_PASSWORD` uyuşmuyor | `docker compose down -v` (VERİYİ SİLER) ve yeniden deploy |

---

## 10. Yeni versiyona geçiş

```bash
cd /opt/sinapp-social
make update
```

Hepsi bu. Her servis için ~10 saniyeden uzun bir downtime yaşamazsın.
