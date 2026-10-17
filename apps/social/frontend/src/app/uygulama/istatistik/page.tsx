"use client";

import { useEffect, useState } from "react";
import { Panel, buttonClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Insight = { id: number; metric_date?: string; impressions?: number; reach?: number; synced_at?: string };

export default function InsightsPage() {
  const { tr } = useLocale();
  const [accounts, setAccounts] = useState<Insight[]>([]);
  const [posts, setPosts] = useState<Insight[]>([]);

  async function load() {
    const result = await api<{ accounts: Insight[]; posts: Insight[] }>("/insights");
    if (result.ok) {
      setAccounts(result.data.accounts);
      setPosts(result.data.posts);
    }
  }

  useEffect(() => {
    load();
  }, []);

  return (
    <Panel title={tr ? "Organik istatistik" : "Organic insights"}>
      <button className={buttonClass} type="button" onClick={() => api("/accounts/sync", { method: "POST" }).then(load)}>
        {tr ? "Yeniden çek" : "Refresh"}
      </button>
      <div className="mt-6 grid gap-6 md:grid-cols-2">
        <section>
          <h2 className="text-xl">{tr ? "Hesaplar" : "Accounts"}</h2>
          <p className="text-sm text-muted">{accounts.length} {tr ? "kayıt" : "rows"}</p>
        </section>
        <section>
          <h2 className="text-xl">{tr ? "Gönderiler" : "Posts"}</h2>
          <p className="text-sm text-muted">{posts.length} {tr ? "kayıt" : "rows"}</p>
        </section>
      </div>
    </Panel>
  );
}
