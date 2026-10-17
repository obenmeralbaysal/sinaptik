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
    <>
      <SiteHeader />
      <main className="mx-auto max-w-6xl px-6">
        <h1 className="text-5xl">{tr ? "Fiyatlar" : "Pricing"}</h1>
        <p className="mt-3 max-w-2xl text-muted">
          {tr ? "Fiyatlar örnek taslaktır ve yönetim panelinden değiştirilir. Yıllık ödeme yaklaşık iki ay indirimlidir." : "Prices are placeholders and can be edited in the admin panel. Yearly billing is about two months free."}
        </p>
        <button type="button" className="mt-6 rounded-full border border-line px-4 py-2 text-sm" onClick={() => setYearly((value) => !value)}>
          {yearly ? (tr ? "Yıllık gösteriliyor" : "Showing yearly") : tr ? "Aylık gösteriliyor" : "Showing monthly"}
        </button>
        {error && <p className="mt-4 text-warn">{error}</p>}
        <div className="mt-8 grid gap-4 md:grid-cols-3">
          {plans.map((plan) => (
            <article key={plan.code} className="flex flex-col rounded-3xl border border-line bg-white p-6">
              <h2 className="text-3xl">{plan.name}</h2>
              <p className="mt-2 text-sm text-muted">{plan.description}</p>
              <p className="mt-6 text-4xl">{money(yearly ? plan.price_yearly_minor : plan.price_monthly_minor)}</p>
              <p className="text-xs uppercase tracking-wide text-muted">{yearly ? (tr ? "yıllık" : "yearly") : tr ? "aylık" : "monthly"}</p>
              <ul className="mt-4 space-y-1 text-sm text-muted">
                <li>{tr ? "Hesap" : "Accounts"}: {plan.limits.social_accounts ?? "∞"}</li>
                <li>{tr ? "Gönderi / ay" : "Posts / month"}: {plan.limits.posts_per_month ?? (tr ? "sınırsız" : "unlimited")}</li>
                <li>{tr ? "Koltuk" : "Seats"}: {plan.limits.seats ?? "∞"}</li>
              </ul>
              <Link href="/kayit" className="mt-6 rounded-full bg-accent px-4 py-2 text-center text-cream">
                {plan.code === "pro" ? (tr ? "14 gün dene" : "Try 14 days") : tr ? "Başla" : "Start"}
              </Link>
            </article>
          ))}
        </div>
      </main>
      <SiteFooter />
    </>
  );
}
