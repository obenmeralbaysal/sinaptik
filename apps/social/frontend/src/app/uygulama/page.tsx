"use client";

import { useEffect, useState } from "react";
import { Panel } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Snapshot = {
  workspace: { name: string; status: string; trial_ends_at: string | null; read_only_at: string | null };
  membership: { role: string; ads_write: boolean };
  usage: Record<string, { used?: number; limit?: number | null } | number | boolean>;
};

export default function DashboardPage() {
  const { tr } = useLocale();
  const [data, setData] = useState<Snapshot | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api<Snapshot>("/workspace").then((result) => {
      if (result.ok) {
        setData(result.data);
      } else if (result.status !== 409) {
        setError(result.error);
      }
    });
  }, []);

  return (
    <Panel title={data?.workspace.name || (tr ? "Özet" : "Overview")}>
      {error && <p className="text-warn">{error}</p>}
      {data && (
        <div className="space-y-4">
          <p className="text-muted">
            {tr ? "Rol" : "Role"}: {data.membership.role}
            {data.membership.ads_write ? " · ads.write" : ""}
            {" · "}
            {data.workspace.status}
          </p>
          <div className="grid gap-3 md:grid-cols-3">
            {Object.entries(data.usage).map(([key, value]) => {
              if (typeof value !== "object" || value === null || typeof value.used !== "number") {
                return null;
              }
              return (
                <div key={key} className="rounded-2xl bg-cream p-4">
                  <p className="text-xs uppercase tracking-wide text-muted">{key}</p>
                  <p className="mt-2 text-2xl">
                    {value.used}
                    <span className="text-base text-muted"> / {value.limit ?? "∞"}</span>
                  </p>
                </div>
              );
            })}
          </div>
        </div>
      )}
    </Panel>
  );
}
