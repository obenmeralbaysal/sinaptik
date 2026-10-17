# Marka

Ürün adı **Sinaptik Social**dır. Pano görselindeki "sinapp social" yazısı kullanılmaz. Alan adı `social.sinaptik.com.tr` kalır. Şirket adı Sinapp (sinapp.com.tr faturalama merkezi) üründen ayrıdır. Compose proje adı `sinsocial`, konteyner ve veritabanı adları iç kimlik olarak durur.

## Renkler

| Rol | Ad | Hex | Kullanım |
| --- | --- | --- | --- |
| Birincil | Deep Navy | `#0F172A` | Arayüz, başlık, menü, koyu kenar çubuğu |
| Zemin | Soft White | `#F8FAFC` | Arka plan, kart, boş alan |
| Vurgu | Electric Indigo | `#6366F1` | Düğme, seçili öğe, yapay zeka |
| Eylem | Teal | `#14B8A6` | CTA, bağlantı, durum, başarı |
| Aksan | Lime | `#A3E635` | Yeni özellik, öne çıkan metrik |
| Metin | Slate | `#334155` | Gövde metni, ikon, açıklama |

Tokenlar `frontend/src/app/globals.css` içindedir: `:root` değişkenleri ve Tailwind `@theme`. Filament birincil rengi `#6366F1`. Yazı tipi Plus Jakarta Sans, `next/font` ile kendinden barındırılır.

Marka gradyanı yalnızca logo ve kahraman vurgudadır: lacivert → indigo → teal → lime.

## Logoyu değiştirmek

Yer tutucu, gradyan bir "S" kurdelesi ve `sinaptik` / `social` yazısıdır. Nihai logo gelince şu üç yeri birlikte değiştirin:

1. `frontend/src/components/logo.tsx` — `LogoMark` ve `Logo`. Arayüzdeki bütün kullanımlar bu bileşendedir.
2. `frontend/src/app/icon.svg` — Next.js favicon. `favicon.ico` tutulmaz.
3. `backend/public/brand/mark.svg` — Filament panel logosu ve favicon (`AdminPanelProvider`).

`Logo` tek bileşendir. `tone` `on-light` veya `on-navy` olur. Yeni dosyayı bu bileşenin içine koyun; sayfalara ayrı logo yapıştırmayın.
