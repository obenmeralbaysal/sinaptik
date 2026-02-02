# Sinapp Social Proje Kuralları ve Operasyon Rehberi

Bu dosya, projenin sürekliliğini, güvenliğini ve Docker uyumluluğunu sağlamak için uyulması gereken kuralları içerir.

## 1. Docker Operasyon Kuralları

> [!IMPORTANT]
> Tüm PHP ve konsol komutları doğrudan sistemde değil, Docker konteyneri üzerinde çalıştırılmalıdır.

**Yanlış:** `php bin/console.php ...`  
**Doğru:** `docker-compose exec app php bin/console.php ...`

**Diğer Komut Örnekleri:**
- Composer: `docker-compose exec app composer install`
- Logları İzleme: `docker-compose logs -f app`

## 2. Veritabanı ve Migration Düzeni

Migration dosyaları `/database/migrations/` altında tutulmalı ve SQL dosyaları şu formatta isimlendirilmelidir:
`YYYY_MM_DD_HHMMSS_aciklama.sql`

**Migration Listesi (Sırasıyla):**
1. [x] `2024_02_01_000000_initial_schema.sql` (Main schema)
2. [x] `2024_02_01_120000_saas_extension.sql` (SaaS features: users, plans, payments)

**Migration Çalıştırma:**
```bash
docker-compose exec -T db mysql -u root -p"$DB_ROOT_PASSWORD" "$DB_DATABASE" < database/migrations/dosya_adi.sql
```

## 3. Güvenlik Prensipleri

- **.env Güvenliği:** Asla `.env` dosyasını Git'e pushlamayın. Şablonları `.env.example` üzerinden paylaşın.
- **Şifreleme:** Hassas API keyleri veritabanında saklanırken uygulama seviyesinde şifrelenmelidir.
- **MySQL Erişimi:** Root kullanıcısı sadece yönetimsel işler için kullanılmalı, uygulama `DB_USERNAME` ile bağlanmalıdır.

## 4. Dosya ve Klasör İzinleri

Storage ve logs klasörleri yazılabilir olmalıdır:
```bash
docker-compose exec app chmod -R 775 storage
```
