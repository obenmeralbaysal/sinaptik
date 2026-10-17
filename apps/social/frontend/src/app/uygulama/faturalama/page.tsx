"use client";

import { useEffect, useState } from "react";
import { Panel, buttonClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api, money } from "@/lib/api";

type Plan = { code: string; name: string; price_monthly_minor: number; price_yearly_minor: number };
type Subscription = { status: string; period: string; plan?: { name: string } | null };
type Payment = { id: number; amount_minor: number; status: string; created_at: string };

export default function BillingPage() {
  const { tr } = useLocale();
  const [plans, setPlans] = useState<Plan[]>([]);
  const [subscription, setSubscription] = useState<Subscription | null>(null);
  const [payments, setPayments] = useState<Payment[]>([]);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    api<{ plans: Plan[] }>("/plans").then((result) => {
      if (result.ok) {
        setPlans(result.data.plans);
      }
    });
    api<{ subscription: Subscription | null; payments: Payment[] }>("/billing/subscription").then((result) => {
      if (result.ok) {
        setSubscription(result.data.subscription);
        setPayments(result.data.payments);
      }
    });
  }, []);

  async function checkout(code: string, period: "monthly" | "yearly") {
    const result = await api<{ url?: string; downgrade?: boolean }>("/billing/checkout", {
      method: "POST",
      body: JSON.stringify({ plan_code: code, period }),
    });
    if (!result.ok) {
      setMessage(result.error);
      return;
    }
    if (result.data.downgrade) {
      setMessage(tr ? "Düşürme dönem sonunda uygulanacak." : "Downgrade applies at period end.");
      return;
    }
    if (result.data.url) {
      window.location.href = result.data.url;
    }
  }

  return (
    <Panel title={tr ? "Faturalama" : "Billing"}>
      <p className="text-sm text-teal">{tr ? "Ödemeler Sinapp güvencesiyle alınır. Tahsilat sinapp.com.tr ödeme merkezinden geçer; kart bu uygulamada tutulmaz." : "Payments are taken with Sinapp at sinapp.com.tr. Cards are not stored here."}</p>
      <p className="mt-2 text-sm text-muted">
        {subscription ? `${subscription.plan?.name || ""} · ${subscription.status} · ${subscription.period}` : tr ? "Abonelik yok. Deneme, çalışma alanı açılınca başlar." : "No subscription yet."}
      </p>
      {message && <p className="mt-3 text-sm">{message}</p>}
      <div className="mt-6 grid gap-3 md:grid-cols-3">
        {plans.map((plan) => (
          <article key={plan.code} className="rounded-2xl border border-line p-4">
            <h2 className="text-2xl">{plan.name}</h2>
            <p className="mt-2">{money(plan.price_monthly_minor)} / {tr ? "ay" : "mo"}</p>
            <div className="mt-3 flex gap-2">
              <button className={buttonClass} type="button" onClick={() => checkout(plan.code, "monthly")}>{tr ? "Aylık" : "Monthly"}</button>
              <button className="rounded-full border border-ink px-4 py-2 text-sm" type="button" onClick={() => checkout(plan.code, "yearly")}>{tr ? "Yıllık" : "Yearly"}</button>
            </div>
          </article>
        ))}
      </div>
      <h2 className="mt-8 text-xl">{tr ? "Ödemeler" : "Payments"}</h2>
      <ul className="mt-2 text-sm">
        {payments.map((payment) => (
          <li key={payment.id}>{money(payment.amount_minor)} · {payment.status}</li>
        ))}
      </ul>
    </Panel>
  );
}
