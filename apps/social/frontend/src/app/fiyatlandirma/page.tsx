"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { SiteFooter, SiteHeader } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api, money } from "@/lib/api";

type Plan = {
  code: string;
  name: string;
  description: string;
  price_monthly_minor: number;
  price_yearly_minor: number;
  limits: Record<string, number | boolean | null>;
};

export default function PricingPage() {
  const { tr } = useLocale();
  const [yearly, setYearly] = useState(false);
  const [plans, setPlans] = useState<Plan[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api<{ plans: Plan[] }>("/plans").then((result) => {
      if (result.ok) {
        setPlans(result.data.plans);
      } else {
        setError(result.error);
      }
    });
  }, []);

  return (
    <div className="landing min-h-screen">
      <SiteHeader tone="navy" />
      <main className="mx-auto max-w-6xl px-6 pb-16">
        <h1 className="text-5xl text-[#F8FAFC]">{tr ? "Fiyatlar" : "Pricing"}</h1>
        <p className="mt-3 max-w-2xl text-[#F8FAFC]/75">
          {tr
            ? "14 gün ücretsiz deneme, kart gerekmez. Fiyatlar TL cinsindendir ve yönetim panelinden değiştirilir. Ücretli yükseltme, çalışma alanındaki faturalama ekranından Sinapp ödeme merkezine gider."
            : "14-day free trial, no card. Prices are in TRY and can be edited in the admin panel. Paid upgrades go through workspace billing and the Sinapp hub."}
        </p>
        <p className="mt-3 text-sm font-semibold text-[#14B8A6]">
          {tr ? "Ödemeler Sinapp güvencesiyle alınır." : "Payments are taken with Sinapp."}{" "}
          <a href="https://sinapp.com.tr" className="underline">sinapp.com.tr</a>
        </p>
        <button type="button" className="mt-6 rounded-full border border-white/20 px-4 py-2 text-sm text-[#F8FAFC]" onClick={() => setYearly((value) => !value)}>
          {yearly ? (tr ? "Yıllık gösteriliyor" : "Showing yearly") : tr ? "Aylık gösteriliyor" : "Showing monthly"}
        </button>
        {error && <p className="mt-4 text-warn">{error}</p>}
        <div className="mt-8 grid gap-4 md:grid-cols-3">
          {plans.map((plan) => (
            <article key={plan.code} className={`flex flex-col rounded-3xl border p-6 ${plan.code === "pro" ? "border-indigo bg-indigo/10" : "border-white/10 bg-white/5"}`}>
              <h2 className="text-3xl text-[#F8FAFC]">{plan.name}</h2>
              <p className="mt-2 text-sm text-[#F8FAFC]/70">{plan.description}</p>
              <p className="mt-6 text-4xl text-[#F8FAFC]">{money(yearly ? plan.price_yearly_minor : plan.price_monthly_minor)}</p>
              <p className="text-xs uppercase tracking-wide text-[#F8FAFC]/50">{yearly ? (tr ? "yıllık" : "yearly") : tr ? "aylık" : "monthly"}</p>
              <ul className="mt-4 space-y-1 text-sm text-[#F8FAFC]/70">
                <li>{tr ? "Hesap" : "Accounts"}: {plan.limits.social_accounts ?? "∞"}</li>
                <li>{tr ? "Gönderi / ay" : "Posts / month"}: {plan.limits.posts_per_month ?? (tr ? "sınırsız" : "unlimited")}</li>
                <li>{tr ? "Koltuk" : "Seats"}: {plan.limits.seats ?? "∞"}</li>
              </ul>
              <Link href="/kayit" className={`mt-6 rounded-2xl px-4 py-2 text-center font-semibold ${plan.code === "pro" ? "bg-teal text-[#0F172A]" : "bg-indigo text-[#F8FAFC]"}`}>
                {tr ? "14 gün ücretsiz deneme" : "14-day free trial"}
              </Link>
            </article>
          ))}
        </div>
      </main>
      <SiteFooter tone="navy" />
    </div>
  );
}
