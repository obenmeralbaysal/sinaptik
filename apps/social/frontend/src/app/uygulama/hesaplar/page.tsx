"use client";

import { useEffect, useState } from "react";
import { Panel, buttonClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Account = { id: number; platform: string; name: string; username: string | null; status: string; health_error: string | null };
type MetaConsent = { text: string; text_version: string; notice_url: string; granted: boolean };

const NOTICE_MARKER = "[Aydınlatma Metni]";

function ConsentCopy({ text, noticeUrl }: { text: string; noticeUrl: string }) {
  const parts = text.split(NOTICE_MARKER);
  if (parts.length === 1) {
    return <>{text}</>;
  }

  return (
    <>
      {parts[0]}
      <a
        href={noticeUrl}
        className="underline"
        onClick={(event) => event.stopPropagation()}
      >
        Aydınlatma Metni
      </a>
      {parts.slice(1).join(NOTICE_MARKER)}
    </>
  );
}

export default function AccountsPage() {
  const { tr } = useLocale();
  const [accounts, setAccounts] = useState<Account[]>([]);
  const [consent, setConsent] = useState<MetaConsent | null>(null);
  const [accepted, setAccepted] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function load() {
    const [accountsResult, consentResult] = await Promise.all([
      api<{ accounts: Account[] }>("/accounts"),
      api<MetaConsent>("/meta/consent"),
    ]);
    if (accountsResult.ok) {
      setAccounts(accountsResult.data.accounts);
    } else {
      setError(accountsResult.error);
    }
    if (consentResult.ok) {
      setConsent(consentResult.data);
    } else {
      setError(consentResult.error);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function connect() {
    if (!accepted || !consent) {
      return;
    }
    const result = await api<{ url: string }>("/meta/connect", {
      method: "POST",
      body: JSON.stringify({ consent: true, text_version: consent.text_version }),
    });
    if (!result.ok) {
      setError(result.error);
      return;
    }
    window.location.href = result.data.url;
  }

  async function withdraw() {
    const result = await api("/meta/connection", { method: "DELETE" });
    if (!result.ok) {
      setError(result.error);
      return;
    }
    setAccepted(false);
    await load();
  }

  const connected = accounts.some((account) => account.status === "connected");

  return (
    <Panel title={tr ? "Bağlı hesaplar" : "Connected accounts"}>
      {consent && (
        <label className="mb-4 flex items-start gap-3 text-sm text-ink">
          <input
            type="checkbox"
            className="mt-1"
            checked={accepted}
            onChange={(event) => setAccepted(event.target.checked)}
          />
          <span>
            <ConsentCopy text={consent.text} noticeUrl={consent.notice_url} />
          </span>
        </label>
      )}
      <button className={buttonClass} type="button" disabled={!accepted || !consent} onClick={connect}>
        {tr ? "Meta ile bağlan" : "Connect Meta"}
      </button>
      <button className={`${buttonClass} ml-3 bg-ink`} type="button" onClick={() => api("/accounts/sync", { method: "POST" }).then(load)}>
        {tr ? "Eşitle" : "Sync"}
      </button>
      {connected && (
        <button className={`${buttonClass} ml-3 bg-ink`} type="button" onClick={withdraw}>
          {tr ? "Bağlantıyı kaldır" : "Disconnect"}
        </button>
      )}
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
