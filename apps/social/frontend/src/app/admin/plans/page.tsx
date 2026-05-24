"use client";

import { Trash2 } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { extractError } from "@/lib/api";
import { useAdminPlans, useDeletePlan, useUpsertPlan } from "@/lib/admin";

export default function AdminPlansPage() {
  const { data: plans = [] } = useAdminPlans();
  const upsert = useUpsertPlan();
  const remove = useDeletePlan();

  const [draft, setDraft] = useState({
    slug: "",
    name: "",
    price_cents: 0,
    currency: "TRY",
    x_account_limit: 1,
    daily_post_limit: 0,
    daily_reply_limit: 0,
  });

  const set = <K extends keyof typeof draft>(k: K, v: (typeof draft)[K]) =>
    setDraft((d) => ({ ...d, [k]: v }));

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-semibold">Plans</h1>

      <Card>
        <CardHeader>
          <CardTitle>New plan</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-3">
          <div className="space-y-2">
            <Label>Slug</Label>
            <Input value={draft.slug} onChange={(e) => set("slug", e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Name</Label>
            <Input value={draft.name} onChange={(e) => set("name", e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Price (cents)</Label>
            <Input
              type="number"
              value={draft.price_cents}
              onChange={(e) => set("price_cents", Number(e.target.value))}
            />
          </div>
          <div className="space-y-2">
            <Label>X account limit</Label>
            <Input
              type="number"
              value={draft.x_account_limit}
              onChange={(e) => set("x_account_limit", Number(e.target.value))}
            />
          </div>
          <div className="space-y-2">
            <Label>Daily post limit</Label>
            <Input
              type="number"
              value={draft.daily_post_limit}
              onChange={(e) => set("daily_post_limit", Number(e.target.value))}
            />
          </div>
          <div className="space-y-2">
            <Label>Daily reply limit</Label>
            <Input
              type="number"
              value={draft.daily_reply_limit}
              onChange={(e) => set("daily_reply_limit", Number(e.target.value))}
            />
          </div>
          <div className="md:col-span-3">
            <Button
              disabled={!draft.slug || !draft.name || upsert.isPending}
              onClick={() =>
                upsert.mutate(draft, {
                  onSuccess: () => {
                    toast.success("Plan saved.");
                    setDraft({ ...draft, slug: "", name: "" });
                  },
                  onError: (err) => toast.error(extractError(err)),
                })
              }
            >
              Save plan
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="p-0">
          <table className="w-full text-sm">
            <thead className="border-b text-left">
              <tr>
                <th className="px-4 py-2 font-medium">Slug</th>
                <th className="px-4 py-2 font-medium">Name</th>
                <th className="px-4 py-2 font-medium">Price</th>
                <th className="px-4 py-2 font-medium">Limits</th>
                <th className="px-4 py-2 font-medium">Active</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {plans.map((p) => (
                <tr key={p.id} className="border-b last:border-0">
                  <td className="px-4 py-2">{p.slug}</td>
                  <td className="px-4 py-2">{p.name}</td>
                  <td className="px-4 py-2">
                    {(p.price_cents / 100).toFixed(2)} {p.currency}
                  </td>
                  <td className="px-4 py-2 text-muted-foreground">
                    {p.x_account_limit} acc · {p.daily_post_limit} posts · {p.daily_reply_limit} replies
                  </td>
                  <td className="px-4 py-2">{p.is_active ? "yes" : "no"}</td>
                  <td className="px-4 py-2">
                    <Button
                      size="sm"
                      variant="ghost"
                      onClick={() => {
                        if (confirm("Delete plan?")) remove.mutate(p.id);
                      }}
                    >
                      <Trash2 className="h-3 w-3" />
                    </Button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>
  );
}
