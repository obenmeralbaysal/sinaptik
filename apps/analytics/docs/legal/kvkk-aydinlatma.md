# TASLAK – yayınlanmadı

Bu metin yerel taslaktır. Yayımlanmaz ve ürün arayüzüne bağlanmaz. Bilinmeyen adres, iletişim ve süre alanları `[DOLDURULACAK]` bırakılmıştır.

## Veri sorumlusu

| | |
| --- | --- |
| Unvan | Sinapp Sağlık Eğitim Yazılım Danışmanlık Ltd. Şti. |
| MERSİS | 0770122255100001 |
| Ticaret sicil no | 1035284 |
| Adres | [DOLDURULACAK] |
| E-posta | [DOLDURULACAK] |
| Telefon | [DOLDURULACAK] |
| KEP | [DOLDURULACAK] |
| VERBİS | [DOLDURULACAK] |

## Kapsam

Bu taslak, Sinaptik Analytics uygulamasının kendi kullanıcılarına yönelik aydınlatma iskeletidir. Müşterinin kendi sitesinin ziyaretçilerine yapılacak aydınlatma bu metnin konusu değildir: [DOLDURULACAK].

## İşlenebilecek veriler

Uygulamanın bugünkü veri modeline göre taslak kategori listesi:

- Kimlik yansısı: OIDC `sub` (`external_subject_id`) ve `iss` (`issuer`). Asıl hesap Zitadel'dedir; bu uygulamadaki satır yansıdır.
- İletişim önbelleği: ad ve e-posta. Yerel sürücüde ayrıca parola özeti.
- Çalışma alanı adı, üyelik rolü (owner, admin, üye).
- Site adı ve alan adı; varsa bağlanan Google mülk kimliği.
- Abonelik durumu, plan kodu, dönem sonu ve `product_code` (`analytics`).
- Ödeme kaydının durumu ve işlem kimliği. Kart verisi bu uygulamada tutulmaz; ödeme kuruluşu [DOLDURULACAK].
- Oturum: `amr` (MFA bilgisi) veritabanına yazılmaz; oturumda durur.

Bunların dışında bir kategori eklenmeyecekse liste böyle kalır. Hukuki nitelendirme (kimlik, iletişim, müşteri işlem, işlem güvenliği) kategori bazında [DOLDURULACAK].

## Amaçlar

- Ortak Sinaptik hesabı ile giriş ve yerel yansı.
- Çalışma alanı, site ve `analytics` aboneliğinin yürütülmesi.
- Owner ve admin işlemlerinde MFA kontrolü.
- Kullanıcı bağlarsa Search Console ve GA4 verisinin okunması.

Her amaç için hukuki sebep (KVKK m.5 ve varsa m.6) seçilmemiştir: [DOLDURULACAK].

## Aktarım

Olası alıcılar, sözleşme kurulmadan yazılmayacaktır:

- Kimlik: Zitadel (üretimde `auth.sinaptik.com.tr`; yerel geliştirmede yalnız `127.0.0.1`).
- Abonelik olayları: Sinapp abonelik bildirimi. Kuruluş unvanı ve sözleşmesi [DOLDURULACAK].
- Kullanıcı Google bağlantısını kurarsa: Google Search Console / GA4. Yurt dışına aktarım mekanizması [DOLDURULACAK].
- Barındırma ve e-posta altyapısı [DOLDURULACAK].

## Toplama yöntemi

Kısmen otomatik: kayıt veya OIDC callback, abonelik webhook'u, kullanıcının girdiği site kaydı, kullanıcının başlattığı Google bağlantısı. Çerez tablosu ve benzeri teknolojiler [DOLDURULACAK]. Oturum çerezi giriş için kullanılır; adı ve ömrü [DOLDURULACAK].

## Saklama

Süreler veri kategorisi bazında belirlenmemiştir: [DOLDURULACAK].

## İlgili kişi hakları

KVKK m.11 kapsamındaki haklar: öğrenme, işleme amacını öğrenme, aktarılanları bilme, düzeltme, silme veya yok etme, aktarılanlara bildirim, otomatik analize itiraz, zararın giderilmesi. Başvurunun nereye ve nasıl yapılacağı [DOLDURULACAK]. Yanıt süresi ve ücret tarifesi mevzuata bırakılır; başvuru kanalı yazılmadan işletilemez.

## Güncelleme

Yayımlanma tarihi yoktur. Bu dosya taslaktır.
