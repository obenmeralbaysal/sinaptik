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

`pages_show_list`, `pages_read_engagement`, `pages_read_user_content`, `pages_manage_posts`, `pages_manage_engagement`, `read_insights`, `instagram_basic`, `instagram_content_publish`, `instagram_manage_insights`, `instagram_manage_comments`, `business_management`, `ads_read`, `ads_management`.

Yorum okuma ve yanıtlama için şunlar durur: `pages_read_user_content`, `pages_manage_engagement`, `instagram_manage_comments`. `pages_read_user_content`, Meta dokümantasyonunda `pages_manage_engagement` bağımlılığıdır ve Sayfa gönderilerindeki kullanıcı yorumlarını okumak için gerekir. Facebook Login for Business Login Configuration bu izni içermelidir. Webhook aşaması (v1 değil) ayrıca `pages_manage_metadata` ister; v1 kod yolu bunu gerektirmez.

App Review bitene kadar Standard Access geçerlidir: yalnızca uygulamada rolü olan kişiler bağlanabilir. Günlük `debug_token` işi süresi dolan veya iptal edilen hesabı `disconnected` yapar ve bildirim gönderir. Kullanıcı token'ı uzun ömürlü token ile değiştirilir. Sayfa token'ı türetildikten sonra ayrıca exchange edilmez.

## Yorumlar (v1)

Yayınlanmış ve `external_id` taşıyan gönderinin yorumları istek anında Graph'tan çekilir ve `social_comments` tablosuna yazılır.

- Facebook Sayfa: `GET /{page-post-id}/comments`, yanıt `POST /{comment-id}/comments`, gizleme `is_hidden`, silme `DELETE`.
- Instagram: `GET /{ig-media-id}/comments`, yanıt `POST /{ig-comment-id}/replies`, gizleme `hide`, silme `DELETE`.
- Uygulama uçları: `GET /api/posts/{post}/comments`, `GET /api/comments`, `POST /api/comments/sync`, `POST /api/comments/{id}/reply`, `POST /api/comments/{id}/hide`, `DELETE /api/comments/{id}`.
- Günlük kuyruk işi `sync-comments` son 14 gündeki yayınları eşitler. Asıl yol istekle çekmedir.
- Yorum gövdesi ve yazar kullanıcı adı varsayılan 90 gün (`COMMENT_RETENTION_DAYS`) sonra `sinsocial:purge-comments` ile silinir. Ayrıntı: `docs/KVKK-YORUM-NOTU.md`.
- Webhook faz 2'dir. `GET|POST /api/meta/webhooks` abonelik olmadan gövdeyi yok sayar. App Dashboard aboneliği ve `pages_manage_metadata` bu sürümde yoktur. Ekran kaydı metni: `docs/META_APP_REVIEW_SCREENCAST.md`.

`upload_media` MCP aracı dosyayı taşımaz. Dosya `POST /api/media` ile yüklenir. Meta, imzalı ve süreli medya URL'sini çeker.
