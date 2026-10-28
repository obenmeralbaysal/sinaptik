# Meta App Review — ekran kaydı metni

Bu metin Sinaptik Social (`social.sinaptik.com.tr`) için App Review kliplerinin çekim sırasıdır. Her izin ayrı klip olmalıdır. Konuşma Türkçe olabilir; ekranda izin adı İngilizce görünsün. Hesap, uygulamada rolü olan bir test kullanıcısı veya işletme yöneticisi olsun. Sayfa ve Instagram profesyonel hesap önceden bağlı olsun. En az bir Facebook Sayfa gönderisi ve bir Instagram medyası Social üzerinden yayınlanmış olsun (`external_id` dolu).

Ortak açılış (her klipte kısaca):

1. Tarayıcıda gizli pencerede `https://social.sinaptik.com.tr/giris` açın. Social hesabına girin.
2. Çalışma alanında **Hesaplar** (`/uygulama/hesaplar`) sayfasına gidin.
3. Açık rıza kutusunu işaretleyin. **Meta ile bağlan** deyin.
4. Facebook Login for Business ekranında işletmeyi, Sayfayı ve Instagram hesabını seçin. İstenen izin listesinde bu klibin izni görünsün. İzin verin ve Social'a dönün.
5. Bağlı hesap satırında durumun bağlı olduğunu gösterin.

## `pages_read_user_content`

Amaç: Sayfa gönderisindeki kullanıcı yorumunu uygulama içinde okumak. Bu izin `pages_manage_engagement` bağımlılığıdır ve Login Configuration içine eklenmelidir.

1. Ortak açılışı bu izin görünürken tamamlayın.
2. Facebook'ta test Sayfasında, Social'ın yayınladığı gönderinin altında bir kullanıcı yorumu olduğundan emin olun (gerekirse ikinci tarayıcıdan kısa bir yorum yazın).
3. Social'da **Yorumlar** (`/uygulama/yorumlar`) sayfasını açın. **Eşitle** deyin.
4. Yorumun yazar adı ve metninin Social içinde göründüğünü durdurup gösterin. Gönderi kimliğinin Sayfadaki gönderiyle aynı olduğunu sözlü söyleyin.
5. Yorumu yanıtlamayın. Bu klip yalnızca okumadır.

English beat: “We request pages_read_user_content so the workspace can read visitor comments on Page posts it published. Here the comment text appears inside Sinaptik Social after an on-demand Graph read.”

## `pages_manage_engagement`

Amaç: Sayfa adına yoruma yanıt yazmak (ve varsa gizleme veya silme).

1. Ortak açılışı bu izin görünürken tamamlayın.
2. Social **Yorumlar** sayfasında Facebook yorumunu gösterin.
3. Yanıt kutusuna kısa, zararsız bir metin yazın (örnek: “Teşekkürler, not aldık.”). **Yanıtla** deyin.
4. Social listesinde yanıtın göründüğünü gösterin.
5. Aynı anda veya hemen ardından facebook.com üzerinde aynı Sayfa gönderisini açın. Social'dan yazılan yanıtın Sayfada göründüğünü gösterin.
6. İsteğe bağlı: Social'da **Gizle** veya **Sil** deyip sonucun hem Social'da hem Sayfada değiştiğini gösterin.

English beat: “pages_manage_engagement is used to publish a reply as the Page. The reply is created in Sinaptik Social and then shown on the native Facebook post.”

## `instagram_manage_comments`

Amaç: Instagram medyasındaki yorumları listelemek ve yanıtlamak.

1. Ortak açılışı bu izin görünürken tamamlayın. Instagram profesyonel hesap Sayfaya bağlı olsun.
2. Instagram uygulamasında veya sitede, Social'ın yayınladığı medyanın altında bir yorum olduğundan emin olun.
3. Social **Yorumlar** sayfasında **Eşitle** deyin. Instagram yorumunun listelendiğini gösterin (platform etiketi Instagram).
4. Kısa bir yanıt yazın (örnek: “Teşekkürler.”). **Yanıtla** deyin.
5. Instagram uygulamasında aynı medyayı açın. Yanıtın orada göründüğünü gösterin.

English beat: “instagram_manage_comments lists comments on media this app published and posts a reply to the Instagram comment id. The reply is visible in the native Instagram client.”

## Diğer izinler

Bu belgedeki üç klip yorum içindir. Aşağıdakilerin her biri için ayrı kısa klip çekin; bu dosya onların adımlarını tekrar etmez:

- `pages_show_list`, `pages_manage_posts`, `instagram_basic`, `instagram_content_publish` — hesap seçme ve gönderi yayınlama
- `pages_read_engagement`, `read_insights`, `instagram_manage_insights` — istatistik ekranı
- `business_management` — işletme varlıklarını listeleme
- `ads_read`, `ads_management` — reklam okuma ve onaylı bütçe/durum değişikliği

`pages_manage_metadata` bu sürümde istenmez. Yorum webhook'u faz 2'dir (`POST /api/meta/webhooks` şu an gövdeyi işlemez).
