# Sinapp Social Kullanım Kılavuzu 🚀

Sinapp Social'a hoş geldiniz! Bu kılavuz, hesabınızı kurmanıza ve projeyi kullanmaya başlamanıza yardımcı olacaktır.

## 1. Hazırlık ve Gereksinimler

Sistemi kullanabilmek için aşağıdaki yetki ve anahtarlara ihtiyacınız vardır:

### X (Twitter) İçin:
- **X Developer Account:** [developer.x.com](https://developer.x.com) adresinden bir uygulama oluşturun.
- **API Key & Secret:** Uygulama ayarlarından alın.
- **Access Token & Secret:** "User authentication settings" kısmından "Read and Write" yetkisi vererek oluşturun.
- **Bearer Token:** Uygulama detaylarından kopyalayın.

### AI İçin:
- **Gemini API Key:** Google Cloud Console üzerinden ücretsiz alabilirsiniz.
- **OpenAI API Key:** (Opsiyonel) Eğer GPT modellerini kullanmak isterseniz.

## 2. Sisteme Kayıt ve Giriş

1.  Uygulama ana sayfasından "Launch App" butonuna tıklayın.
2.  Kullanıcı adı ve şifrenizle giriş yapın (İlk kurulumda Docker üzerinden oluşturulan kullanıcıyı kullanın).

## 3. X Hesabını Bağlama

1.  Sidebar menüsünden **"X Accounts"** sekmesine gidin.
2.  **"Link New Account"** butonuna tıklayın.
3.  Platform olarak **X (Twitter)** seçin.
4.  Developer portalından aldığınız API anahtarlarını ilgili alanlara girin ve kaydedin.

## 4. Hedef Kullanıcı ve AI Ayarları

Botun kimi takip edeceğini ve nasıl cevap vereceğini belirleyin:

1.  **"Target Users"** menüsüne gidin.
2.  **"Add Target"** diyerek takip edilecek kişinin X kullanıcı adını girin.
3.  **AI Tone (Yapay Zeka Tonu)** seçin:
    - **Supportive (Destekleyici):** Kibar ve onaylayıcı cevaplar.
    - **Ironic/Critical (İronik/Eleştirel):** Hafif alaycı veya sorgulayıcı cevaplar.

## 5. Otomasyonu Takip Etme

- **Dashboard:** Günlük kaç post atıldığını ve kaç yanıt verildiğini canlı izleyin.
- **System Logs:** Hataları ve AI tarafından üretilen içerikleri kontrol edin.

> [!NOTE]
> Bot, günde en fazla 3 özgün post ve 10 otomatik yanıt atacak şekilde limitlendirilmiştir. Bu, hesabınızın güvenliği ve API kotalarınız içindir.
