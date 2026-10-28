# KVKK taslağı — yorum notu

Durum: taslak. Avukat yayınlamadan aydınlatma metnine yapıştırılmamalıdır. Uygulamadaki `/kvkk` sayfası hâlâ yer tutucudur. Bu dosya, İçerik / KVKK taslağındaki “yorum çekilmez” konumunu değiştirmek içindir.

## Kaldırılacak cümle

Eski ürün konumu (taslakta bu anlamda duran cümle, birebir şöyle olmasa da bu anlam):

> Sinaptik Social, Facebook veya Instagram yorumlarını çekmez, saklamaz ve yanıtlamaz.

Bu cümle artık yanlıştır. v1, çalışma alanının yayınladığı Sayfa gönderisi ve Instagram medyası üzerindeki yorumları okur, yanıtlar, gizleyebilir veya silebilir.

## Veri kategorileri — önerilen paragraf

Facebook Sayfası ve Instagram profesyonel hesap üzerinden yayınlanan gönderilere gelen yorumlar işlenir. Kategoriler: yorum metni, yorumun platform kimliği, varsa üst yorum kimliği, yorumun yazıldığı zaman, yazarın platform kimliği, yazarın görünen adı veya kullanıcı adı, yorumun gizli veya silinmiş olduğu bilgisi ve Meta'nın döndürdüğü ham yorum kaydı. Bu veriler ilgili Sayfa veya Instagram hesabını bağlayan çalışma alanının talimatıyla, veri sorumlusu müşteri adına işlenir. Sinaptik Social bu kapsamda veri işleyendir. Yorumu yazan üçüncü kişinin kendi başvurusu, müşterinin aydınlatma yükümlülüğünü kaldırmaz.

## Amaç — önerilen paragraf

Amaç, müşterinin kendi yayınladığı içeriğe gelen yorumları çalışma alanı içinde listelemek, müşteri kullanıcısının yazdığı yanıtı ilgili Facebook Sayfası veya Instagram hesabı adına göndermek ve müşterinin istediği yorumu gizlemek veya silmektir. Hukuki sebep taslakta ayrıca seçilmelidir: sözleşme / müşterinin meşru menfaati ve, yurt dışına aktarım için, mevcut Meta açık rıza metni. Açık rıza metni (`resources/consent/meta.txt`) hesap bağlama ve yurt dışı aktarımı anlatır; yorum kategorilerini aydınlatma metni anlatmalıdır. Amaç reklam profillemesi veya yorum yazarını Social kullanıcısı yapmak değildir.

## Saklama — önerilen paragraf

Yorum metni, yazar kullanıcı adı veya görünen adı, yazarın platform kimliği ve ham Meta kaydı varsayılan olarak yorumun yazıldığı tarihten itibaren 90 gün saklanır (`COMMENT_RETENTION_DAYS`). Süre dolunca `sinsocial:purge-comments` bu alanları siler ve kayıtta `purged_at` bırakır. Platform kimliği ve çalışma alanı ilişkisi, silme talebinin ve denetim izinin tamamlanabilmesi için gövdeden ayrı tutulur; avukat bu kimliğin de silinmesini isterse komut genişletilir. Müşteri çalışma alanını sildiğinde yorum satırları çalışma alanıyla birlikte silinir. Meta tarafındaki yorum Social'daki süreden bağımsızdır; Social'dan silmek Meta'daki kopyayı silmez, Meta'dan silmek için uygulamadaki silme işlemi kullanılır.

## Yerine konacak tek paragraf (eski cümlenin yerine)

> Sinaptik Social, bağlanan Facebook Sayfası ve Instagram profesyonel hesapta sizin yayınladığınız gönderilere gelen yorumların metnini, yazarın platformdaki adı veya kullanıcı adını, platform kimliklerini ve yorum zamanını; yorumları çalışma alanında göstermek, sizin adınıza yanıtlamak, gizlemek veya silmek için işler. Yorum metni ve yazar adı varsayılan olarak 90 gün sonra silinir. Bu işleme, hesap bağlama sırasında alınan açık rıza ve sizinle kurulan hizmet sözleşmesi kapsamında yapılır. Yorumu yazan kişilere karşı aydınlatma yükümlülüğü size aittir.
