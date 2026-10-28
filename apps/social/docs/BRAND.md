# Marka

Ürün adı **Sinaptik Social**dır. Logodaki eski "sinapp / social" yazısı kullanılmaz. Alan adı `social.sinaptik.com.tr` kalır. Şirket ve ödeme merkezi Sinapp'tir (`sinapp.com.tr`). Tahsilat bu uygulamada değildir; faturalama `BillingGateway` üzerinden gider. Compose proje adı `sinsocial`, konteyner ve veritabanı adları iç kimlik olarak durur.

## Renkler

| Rol | Ad | Hex |
| --- | --- | --- |
| Birincil | Deep Navy | `#0F172A` |
| Zemin | Soft White | `#F8FAFC` |
| Vurgu | Electric Indigo | `#6366F1` |
| Eylem | Teal | `#14B8A6` |
| Aksan | Lime | `#A3E635` |
| Metin | Slate | `#334155` |

Tokenlar `frontend/src/app/globals.css` içindedir. Filament birincil rengi `#6366F1`. Yazı tipi Plus Jakarta Sans, `next/font` ile kendinden barındırılır. Logo SVG'leri yazı tipine bağlı değildir; sözcükler yola çevrilmiştir.

## Logo

İşaret, yukarı-sola bakan iki yuvarlatılmış ok imlecidir. Büyük ok Electric Indigo, küçük ok Lime ve öndedir. Aralarındaki boşluk zeminin görünmesidir (maske ile kesilir).

Dosyalar `frontend/public/brand/` ve `backend/public/brand/` içinde aynıdır:

| Dosya | Kullanım |
| --- | --- |
| `mark.svg` | Yalnızca işaret. Favicon kaynağı ve e-posta PNG'sinin kökü. |
| `lockup-horizontal.svg` | Koyu zemin. Navbar, kenar çubuğu, Filament koyu mod. |
| `lockup-horizontal-light.svg` | Açık zemin. "sinaptik" `#0F172A`. |
| `lockup-stacked.svg` | Görseldeki gibi üst üste, koyu zemin. |
| `lockup-stacked-light.svg` | Üst üste, açık zemin. |
| `mark.png` | Yalnızca e-posta istemcileri. SVG'den üretilmiş raster. Tasarım kaynağı değildir. |

Arayüz `frontend/src/components/logo.tsx` içindeki `Logo` bileşeninden bu dosyaları gösterir. `tone` `on-navy` veya `on-light`, `variant` `mark`, `horizontal` veya `stacked` olur.

- Favicon: `frontend/src/app/icon.svg` (lacivert köşeli kare + işaret).
- Filament: `AdminPanelProvider` açık kilit ve koyu kilit, favicon `mark.svg`.
- E-posta başlığı: `backend/resources/views/vendor/mail/html/header.blade.php`.

Logoyu değiştirmek için bu SVG'leri birlikte değiştirin ve `mark.png` dosyasını aynı işaretten yeniden üretin. Bileşene ayrı çizim yapıştırmayın.
