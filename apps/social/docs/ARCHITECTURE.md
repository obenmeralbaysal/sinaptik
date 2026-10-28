# Mimari

Tek PostgreSQL veritabanı. Kiracı tablolarında `workspace_id` vardır. `BelongsToWorkspace` global scope'u boş bağlamda satır döndürmez. Kuyruk işleri `SetWorkspaceContext` ile çalışma alanını taşır.

Hassas tablolarda (hesaplar, tokenlar, gönderiler, medya, reklam verisi, denetim kayıtları, API anahtarları) `FORCE ROW LEVEL SECURITY` açıktır. Uygulama rolü `sinsocial_app` süper kullanıcı değildir ve `BYPASSRLS` yetkisi yoktur. Her istek ve iş `set_config('app.workspace_id', ..., is_local)` çalıştırır. Boş değer `sinsocial_setting_id()` içinde NULL'a döner; Postgres `OR` kısa devre yapmadığı için politika doğrudan `::bigint` dökümü kullanmaz. Filament `/admin` oturumu `app.bypass_rls=on` yazar. Bu ikinci savunma hattıdır: uygulama süreci GUC değerini kendisi set edebilir. Asıl sınır, uygulama rolünün süper kullanıcı olmamasıdır.

HTTP giriş noktası `sinsocial-web` (nginx, `127.0.0.1:3021` ve NPM ağı). `/api`, `/auth`, `/sanctum`, `/admin`, `/mcp`, `/livewire`, `/horizon` ve Filament varlıkları PHP-FPM'e gider. Diğer yollar Next.js standalone sunucusuna gider. `IDENTITY_DRIVER=oidc` iken parola girişi kapalıdır; tarayıcı `GET /auth/login` ve `GET /auth/callback` kullanır. Ayrıntı: `docs/OIDC.md`.

Kuyruklar: `publish`, `media`, `insights-sync`, `ads-sync`, `webhooks`, `default`. Horizon `publish` kuyruğunu ayrı supervisor'da yüksek öncelikle çalıştırır.

Tokenlar `TOKEN_ENCRYPTION_KEY` ile AES-256-CBC şifrelenir. Anahtar dönünce `TOKEN_ENCRYPTION_KEY_PREVIOUS` eski değeri okur. Token API yanıtında ve log gövdesinde yer almaz.

Okuma-yazma kapısı: deneme bitince, dönem sonu iptalde veya 3 başarısız tahsilat / 7 gün dunning sonunda çalışma alanı `read_only` olur ve zamanlanmış gönderiler durur. 30 gün sonra silme uyarısı, ardından 7 günlük silme tarihi yazılır. Kullanıcının kendi sildiği çalışma alanı abonelik durumunu değiştirmez; yalnızca `pending_deletion` ve silme tarihi alır.
