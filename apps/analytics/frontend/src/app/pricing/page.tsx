"use client";

import { useQuery } from "@tanstack/react-query";
import { SiteHeader } from "@/components/chrome";
import { api } from "@/lib/api";
import type { SinappCatalog } from "@/lib/types";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

export default function PricingPage() {
  const plans = useQuery({
    queryKey: ["sinapp-plans"],
    queryFn: async () => {
      const { data } = await api.get<SinappCatalog>("/api/sinapp/plans");
      return data;
    },
  });

  return (
    <main className="min-h-screen bg-canvas">
      <SiteHeader />
      <section className="mx-auto max-w-5xl px-6 py-12">
        <h1 className="text-3xl font-semibold">Fiyatlandırma</h1>
        <p className="mt-2 text-muted-foreground">Planlar /api/sinapp/plans kataloğundan okunur. KDV dahil fiyatlar TRY.</p>
        {plans.isLoading && <p className="mt-8">Planlar yükleniyor...</p>}
        {plans.isError && <p className="mt-8 text-destructive">Plan kataloğu alınamadı.</p>}
        <div className="mt-8 grid gap-4 md:grid-cols-2">
          {plans.data?.plans.filter((plan) => plan.active).map((plan) => (
            <Card key={plan.code} className={plan.highlighted ? "border-indigo" : undefined}>
              <CardHeader>
                <CardTitle>{plan.name}</CardTitle>
                <CardDescription>{plan.interval === "yearly" ? "Yıllık" : "Aylık"}</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <p className="text-3xl font-semibold">
                  {plan.price_gross} {plans.data?.currency}
                </p>
                <ul className="space-y-1 text-sm text-muted-foreground">
                  {plan.features.map((feature) => (
                    <li key={feature}>{feature}</li>
                  ))}
                </ul>
              </CardContent>
            </Card>
          ))}
        </div>
      </section>
    </main>
  );
}
