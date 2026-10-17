# MCP

Taşıma: Streamable HTTP JSON-RPC. Uç nokta: `POST /api/mcp`.

Kimlik:

- `Authorization: Bearer smk_...` API anahtarı. Düz metin bir kez döner, veritabanında SHA-256 tutulur. Son kullanım zamanı ve IP yazılır. İsteğe bağlı marka kısıtı ve scope listesi vardır.
- OAuth 2.1 iskelesi: `GET /.well-known/oauth-authorization-server`, `POST /api/oauth/register`, `GET /api/oauth/authorize`, `POST /api/oauth/token`. PKCE S256 ve refresh dönüşümü vardır. Onay ekranı otomatik kabul eder. Eksik kalanlar: kullanıcıya gösterilen kapsam onay sayfası, istemci gizlisi rotasyonu arayüzü, dinamik kayıtın üretimde kapatılması, RFC 8414 alanlarının hub/MCP istemcileriyle doğrulanması.

Scope'lar: `posts:read`, `posts:write`, `media:write`, `analytics:read`, `ads:read`, `ads:write`, `ads:budget`, `approvals:write`.

Araçlar: `list_accounts`, `upload_media` (HTTP yüklemesine yönlendiren saplama), `create_post`, `schedule_post`, `list_posts`, `get_post_insights`, `list_ad_accounts`, `list_campaigns`, `get_ad_insights`, `propose_optimization`, `apply_optimization`, `update_budget`, `pause_resume`.

Riskli araçlar varsayılan olarak öneri oluşturur. `apply_optimization` onaylı öneriyi guardrail kontrolünden geçirerek uygular. Otomatik uygulama yalnızca çalışma alanı sahibi açtıysa ve plan `ads_auto_apply` izin veriyorsa geçerlidir.

Hız limiti Redis'te dakika kovasıdır. Aylık kota `usage_counters` içine yazılır. Aşım HTTP 429 döner. Her çağrı `audit_logs` tablosuna ve paneldeki API sayfasına düşer.
