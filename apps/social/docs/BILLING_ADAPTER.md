# Faturalama adaptörü

Kart verisi bu uygulamada yoktur. Ödeme sinapp.com.tr merkezindedir. Hub sözleşmesi (`docs/INTEGRATION.md`) henüz kesin değildir. Bütün hub'a özel yol, başlık ve olay adları `backend/config/billing.php` içindedir.

## Varsayılan sözleşme

`POST {SINAPP_HUB_URL}/api/v1/checkout-sessions`

Başlıklar:

- `X-Integration-Key`
- `X-Timestamp`
- `X-Signature` = hex(HMAC-SHA256(secret, timestamp + "." + raw_body))

Gövde: `external_reference` (çalışma alanı id), plan kodu, dönem (`monthly` / `yearly`), müşteri e-posta ve adı, başarı ve iptal URL'leri. Yanıt: oturum id ve `sinapp.com.tr/odeme/...` yönlendirme adresi.

Webhook: `POST /api/webhooks/billing/sinapp`. Aynı imza, ±5 dakika zaman damgası, `hash_equals` ile sabit zamanlı karşılaştırma, `event_id` ile idempotent.

Olaylar:

- `subscription.activated`
- `subscription.renewed`
- `subscription.payment_failed`
- `subscription.canceled`
- `payment.refunded`
- `credit_pack.purchased`
- `addon.purchased`

Yükseltme checkout ile hemen başlar. Daha ucuz plana geçiş checkout açmaz; `pending_plan_id` dönem sonunda uygulanır. Dunning: 3 deneme veya 7 gün, sonra salt okunur.

`BILLING_GATEWAY=fake` yerel checkout'u `{FRONTEND_URL}/faturalama/sahte?session=` adresine yollar. `SinappHubGateway` yukarıdaki varsayımı uygular. Hub dokümanı gelince yalnızca `config/billing.php` ve gateway sınıfı güncellenir.

Doğrulanacaklar: imza gövdesinin gerçekten ham JSON olup olmadığı, dönem adları, iade ve kredi paketi olaylarının alan adları, oransal yükseltmenin hub tarafında hesaplanması.
