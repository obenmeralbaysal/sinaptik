"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { api, setWorkspaceId } from "@/lib/api";
import { useLocale } from "@/components/providers";

const links = [
  { href: "/uygulama", tr: "Özet", en: "Overview" },
  { href: "/uygulama/hesaplar", tr: "Hesaplar", en: "Accounts" },
  { href: "/uygulama/olustur", tr: "Oluştur", en: "Compose" },
  { href: "/uygulama/takvim", tr: "Takvim", en: "Calendar" },
  { href: "/uygulama/medya", tr: "Medya", en: "Media" },
  { href: "/uygulama/onaylar", tr: "Onaylar", en: "Approvals" },
  { href: "/uygulama/istatistik", tr: "İstatistik", en: "Insights" },
  { href: "/uygulama/reklamlar", tr: "Reklamlar", en: "Ads" },
  { href: "/uygulama/ekip", tr: "Ekip", en: "Team" },
  { href: "/uygulama/faturalama", tr: "Faturalama", en: "Billing" },
  { href: "/uygulama/api", tr: "API / MCP", en: "API / MCP" },
  { href: "/uygulama/ayarlar", tr: "Ayarlar", en: "Settings" },
];

export function LocaleToggle() {
  const { locale, setLocale } = useLocale();
  return (
    <button
      type="button"
      className="rounded-full border border-line px-3 py-1 text-xs tracking-wide uppercase"
      onClick={() => setLocale(locale === "tr" ? "en" : "tr")}
    >
      {locale === "tr" ? "EN" : "TR"}
    </button>
  );
}

export function SiteHeader() {
  const { tr } = useLocale();
  return (
    <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
      <Link href="/" className="serif text-2xl tracking-tight">
        Sinapp Social
      </Link>
      <nav className="flex items-center gap-5 text-sm">
        <Link href="/fiyatlandirma">{tr ? "Fiyatlar" : "Pricing"}</Link>
        <Link href="/giris">{tr ? "Giriş" : "Sign in"}</Link>
        <Link href="/kayit" className="rounded-full bg-accent px-4 py-2 text-cream">
          {tr ? "Denemeyi başlat" : "Start trial"}
        </Link>
        <LocaleToggle />
      </nav>
    </header>
  );
}

export function SiteFooter() {
  const { tr } = useLocale();
  return (
    <footer className="mx-auto mt-20 flex max-w-6xl flex-wrap gap-4 px-6 py-10 text-sm text-muted">
      <span>© {new Date().getFullYear()} Sinapp Social</span>
      <Link href="/gizlilik">{tr ? "Gizlilik" : "Privacy"}</Link>
      <Link href="/kosullar">{tr ? "Koşullar" : "Terms"}</Link>
      <Link href="/kvkk">KVKK</Link>
      <Link href="/veri-silme">{tr ? "Veri silme" : "Data deletion"}</Link>
      <Link href="/cerezler">{tr ? "Çerezler" : "Cookies"}</Link>
    </footer>
  );
}

type Me = { user: { id: number; name: string; email: string; email_verified_at: string | null } };
type WorkspaceRow = { id: number; name: string; status: string; read_only_at: string | null };
type WorkspacePayload = { workspace: WorkspaceRow & { approval_required?: boolean }; membership: { role: string } };

export function AppFrame({ children }: { children: React.ReactNode }) {
  const { tr } = useLocale();
  const pathname = usePathname();
  const router = useRouter();
  const [me, setMe] = useState<Me["user"] | null>(null);
  const [workspaces, setWorkspaces] = useState<WorkspaceRow[]>([]);
  const [selected, setSelected] = useState("");
  const [notice, setNotice] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const user = await api<Me>("/user");
      if (!user.ok) {
        router.replace("/giris");
        return;
      }
      if (!cancelled) {
        setMe(user.data.user);
      }
      const list = await api<{ workspaces: WorkspaceRow[] }>("/workspaces");
      if (list.ok && !cancelled) {
        setWorkspaces(list.data.workspaces);
        const stored = localStorage.getItem("sinsocial.workspace");
        const fallback = list.data.workspaces[0]?.id;
        const next = stored || (fallback ? String(fallback) : "");
        if (next) {
          setWorkspaceId(next);
          setSelected(next);
        }
      }
      if (pathname.startsWith("/uygulama/baslangic")) {
        return;
      }
      const current = await api<WorkspacePayload>("/workspace");
      if (!current.ok && current.status === 409) {
        router.replace("/uygulama/baslangic");
        return;
      }
      if (current.ok && current.data.workspace.read_only_at) {
        setNotice(tr ? "Çalışma alanı salt okunur. Zamanlanmış gönderiler duraklatıldı." : "This workspace is read-only. Scheduled posts are paused.");
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [pathname, router, tr]);

  async function logout() {
    await api("/logout", { method: "POST" });
    router.replace("/giris");
  }

  return (
    <div className="min-h-screen">
      <div className="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 py-6 md:grid-cols-[220px_1fr]">
        <aside className="flex flex-col gap-6">
          <Link href="/uygulama" className="serif text-2xl">
            Sinapp Social
          </Link>
          <nav className="flex flex-col gap-1 text-sm">
            {links.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className={`rounded-lg px-3 py-2 ${pathname === link.href ? "bg-ink text-cream" : "hover:bg-white"}`}
              >
                {tr ? link.tr : link.en}
              </Link>
            ))}
          </nav>
          <div className="mt-auto space-y-3 text-sm">
            {workspaces.length > 0 && (
              <select
                className="w-full rounded-lg border border-line bg-white px-2 py-2"
                value={selected}
                onChange={async (event) => {
                  setSelected(event.target.value);
                  setWorkspaceId(event.target.value);
                  await api("/workspace/switch", { method: "POST" });
                  window.location.href = "/uygulama";
                }}
              >
                {workspaces.map((workspace) => (
                  <option key={workspace.id} value={workspace.id}>
                    {workspace.name}
                  </option>
                ))}
              </select>
            )}
            <p className="text-muted">{me?.name}</p>
            <div className="flex gap-3">
              <LocaleToggle />
              <button type="button" onClick={logout} className="text-muted underline">
                {tr ? "Çıkış" : "Log out"}
              </button>
            </div>
          </div>
        </aside>
        <main>
          {notice && <p className="mb-4 rounded-xl bg-warn/10 px-4 py-3 text-sm text-warn">{notice}</p>}
          {children}
        </main>
      </div>
    </div>
  );
}

export function Panel({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="rounded-3xl border border-line bg-white/80 p-6 shadow-sm">
      <h1 className="mb-4 text-3xl">{title}</h1>
      {children}
    </section>
  );
}

export function Field({
  label,
  children,
}: {
  label: string;
  children: React.ReactNode;
}) {
  return (
    <label className="block space-y-1 text-sm">
      <span className="text-muted">{label}</span>
      {children}
    </label>
  );
}

export const inputClass = "w-full rounded-xl border border-line bg-cream px-3 py-2 outline-none focus:border-accent";
export const buttonClass = "rounded-full bg-accent px-4 py-2 text-sm text-cream disabled:opacity-50";
