"use client";

import { FormEvent, useEffect, useState } from "react";
import { Panel, buttonClass, Field, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

const scopes = ["posts:read", "posts:write", "media:write", "analytics:read", "ads:read", "ads:write", "ads:budget", "approvals:write"];
type KeyRow = { id: number; name: string; prefix: string; scopes: string[]; last_used_at: string | null; revoked_at: string | null };
type Audit = { id: number; action: string; tool: string | null; result: string; created_at: string };

export default function ApiPage() {
  const { tr } = useLocale();
  const [keys, setKeys] = useState<KeyRow[]>([]);
  const [audit, setAudit] = useState<Audit[]>([]);
  const [token, setToken] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function load() {
    const [keyResult, auditResult] = await Promise.all([
      api<{ keys: KeyRow[] }>("/api-keys"),
      api<{ audit: Audit[] }>("/audit-logs"),
    ]);
    if (keyResult.ok) {
      setKeys(keyResult.data.keys);
    }
    if (auditResult.ok) {
      setAudit(auditResult.data.audit);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function create(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const selected = scopes.filter((scope) => form.get(scope) === "on");
    const result = await api<{ token: string }>("/api-keys", {
      method: "POST",
      body: JSON.stringify({ name: form.get("name"), scopes: selected }),
    });
    if (!result.ok) {
      setError(result.error);
      return;
    }
    setToken(result.data.token);
    setError(null);
    await load();
  }

  return (
    <Panel title="MCP">
      <p className="mb-4 text-sm text-muted">
        {tr ? "Anahtarlar smk_ ile başlar ve yalnızca bir kez gösterilir. Uç nokta POST /api/mcp." : "Keys start with smk_ and are shown once. Endpoint: POST /api/mcp."}
      </p>
      <form onSubmit={create} className="space-y-3">
        <Field label={tr ? "Ad" : "Name"}><input className={inputClass} name="name" required /></Field>
        <div className="flex flex-wrap gap-3 text-sm">
          {scopes.map((scope) => (
            <label key={scope} className="flex items-center gap-1"><input name={scope} type="checkbox" defaultChecked={scope === "posts:read"} /> {scope}</label>
          ))}
        </div>
        <button className={buttonClass} type="submit">{tr ? "Anahtar üret" : "Create key"}</button>
      </form>
      {error && <p className="mt-3 text-sm text-warn">{error}</p>}
      {token && <p className="mt-3 break-all rounded-xl bg-accent-soft p-3 text-sm">{token}</p>}
      <ul className="mt-6 text-sm">
        {keys.map((key) => (
          <li key={key.id} className="flex justify-between py-2">
            <span>{key.name} · {key.prefix} · {key.revoked_at ? (tr ? "iptal" : "revoked") : (tr ? "aktif" : "active")}</span>
            {!key.revoked_at && <button type="button" className="underline" onClick={() => api(`/api-keys/${key.id}`, { method: "DELETE" }).then(load)}>{tr ? "İptal et" : "Revoke"}</button>}
          </li>
        ))}
      </ul>
      <h2 className="mt-8 text-xl">{tr ? "Denetim kaydı" : "Audit log"}</h2>
      <ul className="mt-2 text-sm text-muted">
        {audit.slice(0, 20).map((row) => (
          <li key={row.id}>{row.action} · {row.tool || "—"} · {row.result}</li>
        ))}
      </ul>
    </Panel>
  );
}
