"use client";

import { Card, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { useAdminStats } from "@/lib/admin";

export default function AdminStatsPage() {
  const { data } = useAdminStats();
  if (!data) return <p className="text-sm text-muted-foreground">Loading...</p>;

  const cards = [
    { label: "Users", value: data.users },
    { label: "Organizations", value: data.organizations },
    { label: "Social accounts", value: data.social_accounts },
    { label: "Posts (total)", value: data.posts_total },
    { label: "Posts pending", value: data.posts_pending },
    { label: "Posts failed", value: data.posts_failed },
  ];

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Stats</h1>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {cards.map((c) => (
          <Card key={c.label}>
            <CardHeader>
              <CardDescription>{c.label}</CardDescription>
              <CardTitle className="text-3xl">{c.value}</CardTitle>
            </CardHeader>
          </Card>
        ))}
      </div>
    </div>
  );
}
