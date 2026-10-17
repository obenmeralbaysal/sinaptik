"use client";

import { useEffect, useState } from "react";
import { Panel, buttonClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Account = { id: number; platform: string; name: string; username: string | null; status: string; health_error: string | null };

export default function AccountsPage() {
  const { tr } = useLocale();
  const [accounts, setAccounts] = useState<Account[]>([]);
  const [error, setError] = useState<string | null>(null);

  async function load() {
    const result = await api<{ accounts: Account[] }>("/accounts");
    if (result.ok) {
      setAccounts(result.data.accounts);
    } else {
      setError(result.error);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function connect() {
    const result = await api<{ url: string }>("/meta/connect", { method: "POST" });
    if (!result.ok) {
      setError(result.error);
      return;
    }
    window.location.href = result.data.url;
  }

  return (
    <Panel title={tr ? "Bağlı hesaplar" : "Connected accounts"}>
      <button className={buttonClass} type="button" onClick={connect}>
        {tr ? "Meta ile bağlan" : "Connect Meta"}
      </button>
      <button className={`${buttonClass} ml-3 bg-ink`} type="button" onClick={() => api("/accounts/sync", { method: "POST" }).then(load)}>
        {tr ? "Eşitle" : "Sync"}
      </button>
      {error && <p className="mt-4 text-sm text-warn">{error}</p>}
      <ul className="mt-6 divide-y divide-line">
        {accounts.map((account) => (
          <li key={account.id} className="flex items-center justify-between py-3">
            <div>
              <p className="font-medium">{account.name}</p>
              <p className="text-sm text-muted">{account.platform} · {account.username || account.status}</p>
            </div>
            <span className="rounded-full bg-accent-soft px-3 py-1 text-xs text-accent">{account.status}</span>
          </li>
        ))}
        {accounts.length === 0 && <li className="py-6 text-sm text-muted">{tr ? "Henüz hesap yok." : "No accounts yet."}</li>}
      </ul>
    </Panel>
  );
}
