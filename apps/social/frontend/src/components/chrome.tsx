"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { api, setWorkspaceId } from "@/lib/api";
import { useLocale } from "@/components/providers";
import { Logo } from "@/components/logo";

const primary = [
  { href: "/uygulama", tr: "Ana Sayfa", en: "Home" },
  { href: "/uygulama/takvim", tr: "Takvim", en: "Calendar" },
  { href: "/uygulama/olustur", tr: "İçerik Üret", en: "Create" },
  { href: "/uygulama/hesaplar", tr: "Hesaplar", en: "Accounts" },
  { href: "/uygulama/istatistik", tr: "Analitik", en: "Analytics" },
  { href: "/uygulama/ayarlar", tr: "Ayarlar", en: "Settings" },
];

const secondary = [
  { href: "/uygulama/medya", tr: "Medya", en: "Media" },
  { href: "/uygulama/onaylar", tr: "Onaylar", en: "Approvals" },
  { href: "/uygulama/reklamlar", tr: "Reklamlar", en: "Ads" },
  { href: "/uygulama/ekip", tr: "Ekip", en: "Team" },
  { href: "/uygulama/faturalama", tr: "Faturalama", en: "Billing" },
  { href: "/uygulama/api", tr: "API / MCP", en: "API / MCP" },
];

function NavIcon({ href }: { href: string }) {
  const common = "h-4 w-4";
  if (href.endsWith("/takvim")) {
    return (
      <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
        <rect x="3" y="5" width="18" height="16" rx="2" />
        <path d="M3 10h18M8 3v4M16 3v4" />
      </svg>
    );
  }
  if (href.endsWith("/olustur")) {
    return (
      <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
        <path d="M12 5v14M5 12h14" />
      </svg>
    );
  }
  if (href.endsWith("/hesaplar")) {
    return (
      <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
        <circle cx="8" cy="9" r="3" />
        <circle cx="16" cy="10" r="2.5" />
        <path d="M3 19c.6-2.6 2.6-4 5-4s4.4 1.4 5 4M14 19c.3-1.6 1.4-2.8 3-3.2 1.2-.3 2.3 0 3.2.8" />
      </svg>
    );
  }
  if (href.endsWith("/istatistik")) {
    return (
      <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
        <path d="M4 19V9M10 19V5M16 19v-7M22 19H2" />
      </svg>
    );
  }
  if (href.endsWith("/ayarlar")) {
    return (
      <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
        <circle cx="12" cy="12" r="3" />
        <path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4" />
      </svg>
    );
  }
  return (
    <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
      <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z" />
    </svg>
  );
}

export function LocaleToggle({ onDark = false }: { onDark?: boolean }) {
  const { locale, setLocale } = useLocale();
  return (
    <button
      type="button"
      className={`rounded-full border px-3 py-1 text-xs font-semibold tracking-wide ${onDark ? "border-white/20 text-[#F8FAFC]" : "border-line text-navy"}`}
      onClick={() => setLocale(locale === "tr" ? "en" : "tr")}
    >
      {locale === "tr" ? "EN" : "TR"}
    </button>
  );
}

export function SiteHeader({ tone = "light" }: { tone?: "light" | "navy" }) {
  const { tr } = useLocale();
  const onNavy = tone === "navy";
  return (
    <header className={`mx-auto flex max-w-6xl items-center justify-between px-6 py-6 ${onNavy ? "text-[#F8FAFC]" : "text-navy"}`}>
      <Link href="/" aria-label="Sinaptik Social">
        <Logo tone={onNavy ? "on-navy" : "on-light"} />
      </Link>
      <nav className="flex items-center gap-4 text-sm font-medium">
        <Link href="/fiyatlandirma" className={onNavy ? "text-[#F8FAFC]/80 hover:text-[#F8FAFC]" : "text-slate hover:text-teal"}>
          {tr ? "Fiyatlar" : "Pricing"}
        </Link>
        <Link href="/giris">{tr ? "Giriş" : "Sign in"}</Link>
        <Link href="/kayit" className="rounded-2xl bg-indigo px-4 py-2 text-[#F8FAFC] shadow-sm">
          {tr ? "Denemeyi başlat" : "Start trial"}
        </Link>
        <LocaleToggle onDark={onNavy} />
      </nav>
    </header>
  );
}

export function SiteFooter({ tone = "light" }: { tone?: "light" | "navy" }) {
  const { tr } = useLocale();
  const onNavy = tone === "navy";
  return (
    <footer className={`mx-auto flex max-w-6xl flex-wrap items-center gap-4 px-6 py-10 text-sm ${onNavy ? "text-[#F8FAFC]/70" : "text-slate"}`}>
      <Logo tone={onNavy ? "on-navy" : "on-light"} markClassName="h-7 w-7" />
      <span>© {new Date().getFullYear()} Sinaptik Social</span>
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

  function itemClass(href: string, mobile = false) {
    const active = pathname === href;
    if (mobile) {
      return `flex min-w-[4.5rem] flex-col items-center gap-1 px-2 py-2 text-[10px] font-semibold ${active ? "text-indigo" : "text-[#F8FAFC]/70"}`;
    }
    return `flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-medium ${active ? "bg-indigo text-[#F8FAFC]" : "text-[#F8FAFC]/75 hover:bg-white/10"}`;
  }

  return (
    <div className="min-h-screen bg-canvas md:grid md:grid-cols-[260px_1fr]">
      <aside className="hidden min-h-screen flex-col bg-navy px-4 py-6 text-[#F8FAFC] md:flex">
        <Link href="/uygulama" aria-label="Sinaptik Social">
          <Logo tone="on-navy" />
        </Link>
        <nav className="mt-8 flex flex-col gap-1">
          {primary.map((link) => (
            <Link key={link.href} href={link.href} className={itemClass(link.href)}>
              <NavIcon href={link.href} />
              {tr ? link.tr : link.en}
            </Link>
          ))}
        </nav>
        <p className="mt-8 px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#F8FAFC]/40">{tr ? "Diğer" : "More"}</p>
        <nav className="mt-2 flex flex-col gap-1">
          {secondary.map((link) => (
            <Link key={link.href} href={link.href} className={itemClass(link.href)}>
              {tr ? link.tr : link.en}
            </Link>
          ))}
        </nav>
        <div className="mt-auto space-y-3 pt-6 text-sm">
          {workspaces.length > 0 && (
            <select
              className="w-full rounded-2xl border border-white/15 bg-white/10 px-3 py-2 text-[#F8FAFC]"
              value={selected}
              onChange={async (event) => {
                setSelected(event.target.value);
                setWorkspaceId(event.target.value);
                await api("/workspace/switch", { method: "POST" });
                window.location.href = "/uygulama";
              }}
            >
              {workspaces.map((workspace) => (
                <option key={workspace.id} value={workspace.id} className="text-navy">
                  {workspace.name}
                </option>
              ))}
            </select>
          )}
          <p className="px-1 text-[#F8FAFC]/80">{me?.name}</p>
          <div className="flex items-center gap-3 px-1">
            <LocaleToggle onDark />
            <button type="button" onClick={logout} className="text-[#14B8A6] underline">
              {tr ? "Çıkış" : "Log out"}
            </button>
          </div>
        </div>
      </aside>
      <div className="min-w-0 pb-24 md:pb-0">
        <main className="px-4 py-5 md:px-8 md:py-8">
          {notice && <p className="mb-4 rounded-2xl bg-teal/15 px-4 py-3 text-sm text-navy">{notice}</p>}
          {children}
        </main>
      </div>
      <nav className="fixed inset-x-0 bottom-0 z-20 flex justify-between overflow-x-auto border-t border-white/10 bg-navy px-1 md:hidden">
        {primary.map((link) => (
          <Link key={link.href} href={link.href} className={itemClass(link.href, true)}>
            <NavIcon href={link.href} />
            {tr ? link.tr.split(" ")[0] : link.en}
          </Link>
        ))}
      </nav>
    </div>
  );
}

export function Panel({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="rounded-3xl border border-line bg-white p-6 shadow-[0_12px_40px_rgba(15,23,42,0.06)]">
      <h1 className="mb-4 text-3xl text-navy">{title}</h1>
      {children}
    </section>
  );
}

export function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <label className="block space-y-1 text-sm">
      <span className="font-medium text-slate">{label}</span>
      {children}
    </label>
  );
}

export const inputClass = "w-full rounded-2xl border border-line bg-canvas px-3 py-2 text-navy outline-none focus:border-indigo";
export const buttonClass = "rounded-2xl bg-indigo px-4 py-2 text-sm font-semibold text-[#F8FAFC] shadow-sm disabled:opacity-50";
