# Deploy

Bu betikler sunucuda elle çalıştırılır. Bu depo hiçbir makineye SSH açmaz ve deploy etmez.

Paylaşılan sunucuda eski yığın duruyor olabilir: `sinapp-postgres`, `sinapp-redis`, `sinapp-backend`, `sinapp-horizon`, `sinapp-scheduler`, `sinapp-frontend`. Bu projenin adı `sinsocial`dir. Konteynerler `sinsocial-web`, `sinsocial-backend`, `sinsocial-horizon`, `sinsocial-scheduler`, `sinsocial-frontend`, `sinsocial-db`, `sinsocial-redis`. Postgres ve Redis porta açılmaz. Eski volume'lar silinmez.

## İlk kurulum

```bash
cp .env.example .env
# APP_KEY boş kalabilir; deploy.sh üretir.
# Üretimde BILLING_GATEWAY=sinapp, META_DRIVER=graph, SESSION_SECURE_COOKIE=true
# SINAPP_INTEGRATION_KEY, SINAPP_INTEGRATION_SECRET ve SINAPP_BASE_URL=https://sinapp.com.tr
# APP_URL ve FRONTEND_URL https://social.sinaptik.com.tr
chmod +x deploy/deploy.sh deploy/backup.sh docker/postgres/init.sh
./deploy/deploy.sh
```

`deploy.sh` imajları kurar, veritabanı ve Redis'i sağlıklı olana kadar bekler, `sinsocial_app` rolünü (süper kullanıcı değil, `BYPASSRLS` yok) oluşturur, `APP_KEY` boşsa üretir, migration ve plan tohumunu çalıştırır, config/route önbelleği ve `php artisan filament:optimize` alır, yığını ayağa kaldırır ve `http://127.0.0.1:3021/api/health` kontrol eder.

NPM ağı `NPM_NETWORK` ile seçilir. Varsayılan ad `nginx-proxy-manager_default`dir. Ağ yoksa `deploy.sh` onu oluşturur. Test sunucusunun dış ağı `root_default`dir; orada `.env` içinde `NPM_NETWORK=root_default` yazın. Ağ zaten varsa betik yenisini açmaz.

## Nginx Proxy Manager

Yeni Proxy Host:

- Domain: `social.sinaptik.com.tr`
- Scheme: `http`
- Forward Hostname: `sinsocial-web`
- Forward Port: `8080`
- Block Common Exploits: açık
- Websockets Support: açık
- SSL: Let's Encrypt, Force SSL, HTTP/2

`sinsocial-web` hem `127.0.0.1:3021` hem de NPM ağına bağlıdır. NPM konteyneri aynı Docker ağından `sinsocial-web` adına çözebilmelidir.

## Eski yığından geçiş

1. Yeni yığını yukarıdaki adımlarla ayağa kaldırın ve `/api/health` yeşil olsun.
2. NPM kaydını eski konteynerden `sinsocial-web:8080` adresine çevirin.
3. Eski compose projesini durdurun (`docker compose stop`). Volume'ları silmeyin.
4. Geri dönüş için eski yığını yeniden başlatıp NPM kaydını eski konteynere almak yeterlidir.

## Süper admin ve yedek

```bash
docker compose exec backend php artisan sinsocial:admin
```

Yedek betiği kendi konumundan depo köküne geçer. Döküm dizini `BACKUP_DIR`dir; `.env` içinde boşsa varsayılan `<depo>/storage/backups` olur. Göreli bir `BACKUP_DIR` depo köküne göre çözülür. Cron satırındaki `<depo>` bu klonun dizinidir:

```bash
15 3 * * * <depo>/deploy/backup.sh >> /var/log/sinsocial-backup.log 2>&1
```

`deploy/backup.sh` `pg_dump` alır, 30 günden eski yerel kopyaları siler. `BACKUP_S3_BUCKET` doluysa host'taki `aws` CLI ile R2/S3'e yükler (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_ENDPOINT`). Ayda bir bu dosyadan geri yükleme deneyin. Yedekler şifreli bir kovada durmalıdır.

## Üretim ortam değişkenleri

Bakınız kök `.env.example`. Sırlar repoda yoktur. `NPM_NETWORK` isteğe bağlıdır; test sunucusu `root_default` kullanır. `BACKUP_DIR` boşsa yedek depo dizinindeki `storage/backups` altına yazılır. Zorunlu üretim değerleri: `APP_KEY`, `DB_PASSWORD`, `POSTGRES_PASSWORD`, `TOKEN_ENCRYPTION_KEY`, `SINAPP_INTEGRATION_KEY`, `SINAPP_INTEGRATION_SECRET`, `SINAPP_BASE_URL`, `META_APP_ID`, `META_APP_SECRET`, `META_CONFIG_ID`. `SINAPP_HUB_KEY` ve `SINAPP_HUB_SECRET` yalnızca eski `BILLING_GATEWAY=sinapp_hub` sürücüsü içindir. `BILLING_GATEWAY=sinapp` satış sayfasına yönlendirir; sırlar boş olsa da süreç ayağa kalkar, imzalı uçlar 401 döner. R2 kullanılacaksa `MEDIA_DISK=s3` ve AWS/R2 alanları. `ADMIN_EMAIL` ile `ADMIN_PASSWORD` yalnızca ilk süper admin içindir. Sırlar loglanmaz.
