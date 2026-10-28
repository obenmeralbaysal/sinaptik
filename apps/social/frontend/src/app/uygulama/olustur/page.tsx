"use client";

import { FormEvent, useEffect, useState } from "react";
import { Panel, buttonClass, Field, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Account = { id: number; name: string; platform: string };

export default function ComposerPage() {
  const { tr } = useLocale();
  const [accounts, setAccounts] = useState<Account[]>([]);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    api<{ accounts: Account[] }>("/accounts").then((result) => {
      if (result.ok) {
        setAccounts(result.data.accounts);
      }
    });
  }, []);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const result = await api("/posts", {
      method: "POST",
      body: JSON.stringify({
        body: form.get("body"),
        post_type: form.get("post_type"),
        social_account_id: form.get("social_account_id") || null,
        scheduled_at: form.get("scheduled_at") || null,
        status: form.get("status"),
      }),
    });
    setMessage(result.ok ? (tr ? "Gönderi kaydedildi." : "Post saved.") : result.error);
  }

  return (
    <Panel title={tr ? "Gönderi oluştur" : "Compose"}>
      <form onSubmit={onSubmit} className="max-w-xl space-y-4">
        <Field label={tr ? "Metin" : "Text"}>
          <textarea className={inputClass} name="body" rows={5} />
        </Field>
        <Field label={tr ? "Tür" : "Type"}>
          <select className={inputClass} name="post_type" defaultValue="feed">
            {["feed", "photo", "carousel", "video", "reel", "story"].map((type) => (
              <option key={type}>{type}</option>
            ))}
          </select>
        </Field>
        <Field label={tr ? "Hesap" : "Account"}>
          <select className={inputClass} name="social_account_id">
            <option value="">{tr ? "Seçilmedi" : "None"}</option>
            {accounts.map((account) => (
              <option key={account.id} value={account.id}>{account.name} · {account.platform}</option>
            ))}
          </select>
        </Field>
        <Field label={tr ? "Zaman" : "Schedule"}>
          <input className={inputClass} name="scheduled_at" type="datetime-local" />
        </Field>
        <Field label={tr ? "Durum" : "Status"}>
          <select className={inputClass} name="status" defaultValue="draft">
            <option value="draft">{tr ? "Taslak" : "Draft"}</option>
            <option value="scheduled">{tr ? "Zamanla" : "Schedule"}</option>
          </select>
        </Field>
        {message && <p className="text-sm">{message}</p>}
        <button className={buttonClass} type="submit">{tr ? "Kaydet" : "Save"}</button>
      </form>
    </Panel>
  );
}
