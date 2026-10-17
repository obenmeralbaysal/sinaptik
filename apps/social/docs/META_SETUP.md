# Meta kurulumu

Mevcut Live Business uygulaması kullanılır. Müşteri bağlantısı Facebook Login for Business ve `config_id` iledir. Sinapp'in kendi çalışma alanı System User token'ı ile bağlanır (`POST /api/meta/system-user`). `META_DRIVER=fake` yerel geliştirmede Graph çağrısı yapmaz.

## Ortam

- `META_APP_ID`
- `META_APP_SECRET`
- `META_CONFIG_ID`
- `META_GRAPH_VERSION` (varsayılan `v23.0`)
- `META_SYSTEM_USER_TOKEN` (yalnızca Sinapp iç çalışma alanı)
- `META_DRIVER=graph` üretimde

## App Dashboard

1. Uygulama tipi Business olsun. Business Verification ve portföy admin rolü duruyor mu kontrol edin.
2. Facebook Login for Business ürününde bir Login Configuration oluşturun. `config_id` değerini `META_CONFIG_ID` yapın.
3. App Domains ve Site URL: `social.sinaptik.com.tr`.
4. OAuth yönlendirme: `https://social.sinaptik.com.tr/api/meta/callback`
5. Veri silme callback: `https://social.sinaptik.com.tr/api/meta/data-deletion`
6. Deauthorize callback: `https://social.sinaptik.com.tr/api/meta/deauthorize`
7. Gizlilik, koşullar ve veri silme sayfaları: `/gizlilik`, `/kosullar`, `/veri-silme`

## İstenen izinler

`pages_show_list`, `pages_read_engagement`, `pages_manage_posts`, `pages_manage_engagement`, `read_insights`, `instagram_basic`, `instagram_content_publish`, `instagram_manage_insights`, `instagram_manage_comments`, `business_management`, `ads_read`, `ads_management`.

App Review bitene kadar Standard Access geçerlidir: yalnızca uygulamada rolü olan kişiler bağlanabilir. Günlük `debug_token` işi süresi dolan veya iptal edilen hesabı `disconnected` yapar ve bildirim gönderir. Kullanıcı token'ı uzun ömürlü token ile değiştirilir. Sayfa token'ı türetildikten sonra ayrıca exchange edilmez.

`upload_media` MCP aracı dosyayı taşımaz. Dosya `POST /api/media` ile yüklenir. Meta, imzalı ve süreli medya URL'sini çeker.
