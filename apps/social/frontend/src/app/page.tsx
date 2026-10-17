"use client";

import Link from "next/link";
import { SiteFooter, SiteHeader } from "@/components/chrome";
import { useLocale } from "@/components/providers";

export default function HomePage() {
  const { tr } = useLocale();
  return (
    <>
      <SiteHeader />
      <main className="mx-auto max-w-6xl px-6">
        <p className="text-sm uppercase tracking-[0.2em] text-accent">{tr ? "Sosyal medya ve reklam" : "Social and ads"}</p>
        <h1 className="mt-4 max-w-3xl text-5xl leading-tight md:text-7xl">
          {tr ? "Paylaşımı planlayın. Reklam değişikliğini önce onaylayın." : "Plan the posts. Approve the ad changes first."}
        </h1>
        <p className="mt-6 max-w-2xl text-lg text-muted">
          {tr
            ? "Sinapp Social, her firmanın kendi çalışma alanında Facebook ve Instagram paylaşımı ile Meta reklamlarını yönetmesini sağlar. Otomatik bütçe yalnızca sahip tarafından ve tavanların içinde açılır."
            : "Sinapp Social gives each company a workspace for Facebook and Instagram publishing and Meta ads. Automatic budgets can be enabled only by the owner, and only inside caps."}
        </p>
        <div className="mt-8 flex gap-3">
          <Link href="/kayit" className="rounded-full bg-ink px-5 py-3 text-cream">
            {tr ? "14 gün Pro, kart yok" : "14-day Pro, no card"}
          </Link>
          <Link href="/fiyatlandirma" className="rounded-full border border-ink px-5 py-3">
            {tr ? "Paketleri gör" : "See plans"}
          </Link>
        </div>
        <div className="mt-16 grid gap-4 md:grid-cols-3">
          {[
            [tr ? "Çalışma alanı" : "Workspace", tr ? "Sahip, yönetici, editör, onaycı ve izleyici. Reklam yazma ayrı bir izin." : "Owner, admin, editor, approver and viewer. Ads writing is a separate permission."],
            [tr ? "Yayın" : "Publishing", tr ? "Taslak, takvim, onay ve Meta'nın çekebileceği imzalı medya adresleri." : "Drafts, calendar, approval, and signed media URLs Meta can fetch."],
            [tr ? "Reklam koruması" : "Ad guardrails", tr ? "Öneri, onay, uygulama. Günlük tavan, yüzde sınırı, bekleme ve acil durdurma." : "Propose, approve, apply. Daily caps, percent limits, cooldown and a kill switch."],
          ].map(([title, body]) => (
            <article key={title} className="rounded-3xl border border-line bg-white/70 p-6">
              <h2 className="text-2xl">{title}</h2>
              <p className="mt-3 text-sm leading-6 text-muted">{body}</p>
            </article>
          ))}
        </div>
      </main>
      <SiteFooter />
    </>
  );
}
