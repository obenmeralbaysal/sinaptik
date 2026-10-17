# Sinapp entegrasyonu

Sinaptik Social, Sinapp ödeme merkezi (`sinapp.com.tr`) üzerinden satılır. Kart verisi bu uygulamada tutulmaz. Fiyatın tek kaynağı `plans` tablosudur; uygulama içindeki fiyatlandırma sayfası ile aşağıdaki katalog aynı satırları okur.

Üretim alan adı: `social.sinaptik.com.tr`.

## Sinapp'in yapılandıracağı adresler

| Amaç | Yöntem | Adres |
| --- | --- | --- |
| Fiyat kataloğu | `GET` | `https://social.sinaptik.com.tr/api/sinapp/plans` |
| Hesap sorgu | `POST` | `https://social.sinaptik.com.tr/api/sinapp/accounts/lookup` |
| Abonelik webhook | `POST` | `https://social.sinaptik.com.tr/api/webhooks/sinapp-subscriptions` |
| Dönüş alanı | tarayıcı | `https://social.sinaptik.com.tr` |

Satın alma sonrası dönüş adresi: `https://social.sinaptik.com.tr/uygulama/faturalama`.

Eski `POST /api/webhooks/billing/sinapp` yolu durur. Sinapp onu çağırmaz. Güncel webhook yukarıdaki `sinapp-subscriptions` adresidir.

## Ortam değişkenleri

| Değişken | Anlam |
| --- | --- |
| `BILLING_GATEWAY` | Üretimde `sinapp`. Yerelde varsayılan `fake`. Eski sunucudan checkout için `sinapp_hub`. |
| `SINAPP_BASE_URL` | `https://sinapp.com.tr` |
| `SINAPP_PURCHASE_PATH` | Satış sayfası yolu. Varsayılan `/satin-al/sinaptik-social`. |
| `SINAPP_INTEGRATION_KEY` | Hesap sorgusundaki `X-Sinapp-Key`. |
| `SINAPP_INTEGRATION_SECRET` | HMAC sırrı. Loglanmaz. |

`SINAPP_HUB_URL`, `SINAPP_HUB_KEY` ve `SINAPP_HUB_SECRET` yalnızca `sinapp_hub` sürücüsüne aittir. Yeni sözleşmede kullanılmaz. Sır boşsa süreç yine ayağa kalkar; imzalı uçlar 401 döner. Katalog imzasızdır ve sır olmadan da çalışır.

Sır değiştirildikten sonra `php artisan config:cache` (deploy betiği bunu zaten yapar) gerekir.

## Satış sayfası

Yükseltme ve ödeme düğmeleri kullanıcıyı şu adrese yollar. E-posta sorguda hazır gelir:

```text
https://sinapp.com.tr/satin-al/sinaptik-social?plan=sinaptik-social-pro-monthly&email=musteri@ornek.com&account_id=42&return_url=https%3A%2F%2Fsocial.sinaptik.com.tr%2Fuygulama%2Ffaturalama%3Fstatus%3Dok&cancel_url=https%3A%2F%2Fsocial.sinaptik.com.tr%2Fuygulama%2Ffaturalama%3Fstatus%3Dcancel
```

| Sorgu | Anlam |
| --- | --- |
| `plan` | Katalog kodu, örneğin `sinaptik-social-pro-monthly` |
| `email` | Oturum açmış sahip |
| `account_id` | Aboneliğin bağlanacağı çalışma alanı id |
| `return_url` | Başarılı dönüş |
| `cancel_url` | Vazgeçme dönüşü |

Sinapp bu yolu barındırmıyorsa `SINAPP_PURCHASE_PATH` ile değiştirilir. Taban adres `SINAPP_BASE_URL`dir.

## Fiyat kataloğu

`GET /api/sinapp/plans` herkese açıktır.

```json
{
  "product": "sinaptik-social",
  "currency": "TRY",
  "vat_rate": 20,
  "plans": [
    {
      "code": "sinaptik-social-pro-monthly",
      "name": "Pro",
      "interval": "monthly",
      "price_net": 1249.17,
      "price_gross": 1499,
      "features": ["10 sosyal hesap"],
      "highlighted": true,
      "active": true
    }
  ],
  "updated_at": "2026-10-17T12:00:00+00:00"
}
```

- Tutarlar TL cinsindendir (kuruş değil). `price_gross`, uygulamada görünen KDV dahil fiyattır (`price_*_minor / 100`). `price_net`, brütün 1,20'ye bölünüp iki haneye yuvarlanmış halidir. KDV oranı 20'dir.
- Her herkese açık plandan iki satır çıkar: `-monthly` ve `-yearly`. İç plan kodları `starter`, `pro`, `agency`dir.
- `highlighted`, Pro (deneme) planda doğrudur.
- `active: false` olan herkese açık plan katalogda kalır; Sinapp satışı kapatabilir. `is_public: false` planlar çıkmaz.
- `features`, plandaki `features` dizisi ve limitlerden üretilen Türkçe satırlardır.
- `updated_at`, katalogdaki planların en yeni `updated_at` değeridir.

Kodlar:

| Plan | Aylık | Yıllık |
| --- | --- | --- |
| Başlangıç | `sinaptik-social-starter-monthly` | `sinaptik-social-starter-yearly` |
| Pro | `sinaptik-social-pro-monthly` | `sinaptik-social-pro-yearly` |
| Ajans | `sinaptik-social-agency-monthly` | `sinaptik-social-agency-yearly` |

## Hesap sorgu

`POST /api/sinapp/accounts/lookup`

```json
{ "email": "musteri@ornek.com" }
```

```json
{ "exists": true, "account_id": 42, "display_name": "Ada Studio" }
```

`account_id` çalışma alanı id'sidir. Abonelik bu id'ye yazılır. E-posta bir sahibinse ve birden fazla çalışma alanı varsa, önce o sahibin seçili alanı, yoksa en eski sahip olunan alan döner. Üye olup sahip olmayan hesap `exists: false` döner. Bilinmeyen e-posta da aynı gövdeyi döner (`account_id` ve `display_name` null). Böylece e-postanın kayıtlı olup olmadığı ayrıca sızmaz.

Başlıklar:

| Başlık | Değer |
| --- | --- |
| `X-Sinapp-Key` | `SINAPP_INTEGRATION_KEY` |
| `X-Sinapp-Timestamp` | Unix saniye |
| `X-Sinapp-Nonce` | Tek kullanımlık, en fazla 128 karakter |
| `X-Sinapp-Signature` | hex HMAC-SHA256 |

İmza metni, satır sonu `\n` ile birleştirilir:

```text
timestamp + "\n" + nonce + "\n" + METHOD + "\n" + path + "\n" + sha256hex(rawBody)
```

`METHOD` büyük harftir (`POST`). `path` sorgu dizesi olmadan `/api/sinapp/accounts/lookup`dir. `rawBody` gönderilen baytların kendisidir. Hex küçük veya büyük harf kabul edilir.

Zaman damgası ±300 saniye. Aynı nonce 10 dakika reddedilir. Anahtar ve IP başına dakikada 30 istek; fazlası `429`. Hatalı imza, eski zaman ve tekrar nonce `401`dir. `2xx` dışındaki yanıtlar yinelenebilir; nonce her denemede yeni olmalıdır.

## Abonelik webhook'u

`POST /api/webhooks/sinapp-subscriptions`

| Başlık | Değer |
| --- | --- |
| `X-Sinapp-Timestamp` | Unix saniye |
| `X-Sinapp-Signature` | hex HMAC-SHA256(secret, timestamp + "." + rawBody) |

Aynı ±300 saniye kuralı. Gövde:

```json
{
  "id": "evt_123",
  "type": "subscription.activated",
  "created_at": "2026-10-17T12:00:00+00:00",
  "data": {
    "subscription_id": "sub_123",
    "product_account_email": "musteri@ornek.com",
    "account_id": 42,
    "plan_code": "sinaptik-social-pro-monthly",
    "period_start": "2026-10-17T12:00:00+00:00",
    "period_end": "2026-11-17T12:00:00+00:00",
    "amount_gross": 1499,
    "amount_net": 1249.17,
    "vat_amount": 249.83,
    "currency": "TRY",
    "billing": {}
  }
}
```

Olaylar:

| Olay | Etki |
| --- | --- |
| `subscription.activated` | Planı bağlar, dönemi yazar, çalışma alanını aktif yapar, tahsilatı kaydeder. |
| `subscription.renewed` | Dönemi uzatır, bekleyen düşürme ve iptal bayrağını temizler, tahsilatı kaydeder. |
| `subscription.canceled` | Dönem sonuna kadar erişim sürer (`cancel_at_period_end`). `period_end` geçmişse erişim hemen kapanır. |
| `subscription.expired` | Erişim hemen salt okunur olur, zamanlanmış gönderiler durur. |

`id` bir kez işlenir. Aynı id tekrar gelirse `200` ve `{"ok": true, "duplicate": true}` döner; abonelik ve ödeme ikinci kez yazılmaz. `account_id` bulunamazsa `404`, plan kodu veya gövde hatalıysa `422`. Sinapp `2xx` dışında yeniden dener. Bilinmeyen olay türü de `422`dir ve kaydedilmez, böylece destek eklenince yeniden deneme işe yarar.

`amount_gross` TL cinsindendir. Kayıt kuruşa çevrilir (`round(gross * 100)`). Para birimi `TRY` olmalıdır.

Plan kodunun soneki dönemi belirler (`monthly` veya `yearly`). Tanınmayan veya kapalı bir plan `422` döner.

## Yerel sürücü

`BILLING_GATEWAY=fake` düğmeleri `{FRONTEND_URL}/faturalama/sahte?session=` adresine yollar. Checkout yanıtında hem `redirect_url` hem `url` vardır; arayüz `url` alanını açar.
