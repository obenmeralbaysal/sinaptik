"use client";

import { FormEvent, useEffect, useState } from "react";
import { Panel, buttonClass, Field, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Workspace = {
  name: string;
  timezone: string;
  locale: string;
  approval_required: boolean;
  require_owner_2fa: boolean;
  ads_auto_apply: boolean;
  ads_kill_switch: boolean;
  ads_daily_cap_minor: number;
  ads_monthly_cap_minor: number;
  ads_max_increase_percent: number;
  ads_cooldown_hours: number;
  pending_deletion: boolean;
};
type Session = { id: string; ip_address: string | null; user_agent: string | null; current: boolean };

export default function SettingsPage() {
  const { tr } = useLocale();
  const [workspace, setWorkspace] = useState<Workspace | null>(null);
  const [sessions, setSessions] = useState<Session[]>([]);
  const [secret, setSecret] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  async function load() {
    const [workspaceResult, sessionResult] = await Promise.all([
      api<{ workspace: Workspace }>("/workspace"),
      api<{ sessions: Session[] }>("/sessions"),
    ]);
    if (workspaceResult.ok) {
      setWorkspace(workspaceResult.data.workspace);
    }
    if (sessionResult.ok) {
      setSessions(sessionResult.data.sessions);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!workspace) {
      return;
    }
    const form = new FormData(event.currentTarget);
    const result = await api("/workspace", {
      method: "PATCH",
      body: JSON.stringify({
        name: form.get("name"),
        timezone: form.get("timezone"),
        locale: form.get("locale"),
        approval_required: form.get("approval_required") === "on",
        require_owner_2fa: form.get("require_owner_2fa") === "on",
      }),
    });
    setMessage(result.ok ? (tr ? "Kaydedildi." : "Saved.") : result.error);
  }

  async function ads(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const result = await api("/workspace/ads-settings", {
      method: "PATCH",
      body: JSON.stringify({
        ads_auto_apply: form.get("ads_auto_apply") === "on",
        ads_kill_switch: form.get("ads_kill_switch") === "on",
        ads_daily_cap_minor: Number(form.get("ads_daily_cap_minor")),
        ads_monthly_cap_minor: Number(form.get("ads_monthly_cap_minor")),
        ads_max_increase_percent: Number(form.get("ads_max_increase_percent")),
        ads_cooldown_hours: Number(form.get("ads_cooldown_hours")),
      }),
    });
    setMessage(result.ok ? (tr ? "Reklam korumaları güncellendi." : "Ad guardrails updated.") : result.error);
  }

  return (
    <div className="space-y-6">
      <Panel title={tr ? "Ayarlar" : "Settings"}>
        {workspace && (
          <form onSubmit={save} className="grid max-w-xl gap-3">
            <Field label={tr ? "Ad" : "Name"}><input className={inputClass} name="name" defaultValue={workspace.name} /></Field>
            <Field label={tr ? "Saat dilimi" : "Timezone"}><input className={inputClass} name="timezone" defaultValue={workspace.timezone} /></Field>
            <Field label={tr ? "Dil" : "Locale"}>
              <select className={inputClass} name="locale" defaultValue={workspace.locale}>
                <option value="tr">Türkçe</option>
                <option value="en">English</option>
              </select>
            </Field>
            <label className="text-sm"><input name="approval_required" type="checkbox" defaultChecked={workspace.approval_required} /> {tr ? "Yayın onayı zorunlu" : "Require post approval"}</label>
            <label className="text-sm"><input name="require_owner_2fa" type="checkbox" defaultChecked={workspace.require_owner_2fa} /> {tr ? "Sahip için 2FA zorunlu" : "Require owner 2FA"}</label>
            <button className={buttonClass} type="submit">{tr ? "Kaydet" : "Save"}</button>
          </form>
        )}
        {message && <p className="mt-3 text-sm">{message}</p>}
      </Panel>
      {workspace && (
        <Panel title={tr ? "Reklam tavanları" : "Ad caps"}>
          <form onSubmit={ads} className="grid max-w-xl gap-3">
            <label className="text-sm"><input name="ads_auto_apply" type="checkbox" defaultChecked={workspace.ads_auto_apply} /> {tr ? "Otomatik uygula (yalnızca sahip, plan izin verirse)" : "Auto-apply (owner only, if the plan allows)"}</label>
            <label className="text-sm"><input name="ads_kill_switch" type="checkbox" defaultChecked={workspace.ads_kill_switch} /> {tr ? "Acil durdurma" : "Kill switch"}</label>
            <Field label={tr ? "Günlük tavan (kuruş)" : "Daily cap (minor)"}><input className={inputClass} name="ads_daily_cap_minor" type="number" defaultValue={workspace.ads_daily_cap_minor} /></Field>
            <Field label={tr ? "Aylık tavan (kuruş)" : "Monthly cap (minor)"}><input className={inputClass} name="ads_monthly_cap_minor" type="number" defaultValue={workspace.ads_monthly_cap_minor} /></Field>
            <Field label={tr ? "Azami artış %" : "Max increase %"}><input className={inputClass} name="ads_max_increase_percent" type="number" defaultValue={workspace.ads_max_increase_percent} /></Field>
            <Field label={tr ? "Bekleme (saat)" : "Cooldown (hours)"}><input className={inputClass} name="ads_cooldown_hours" type="number" defaultValue={workspace.ads_cooldown_hours} /></Field>
            <button className={buttonClass} type="submit">{tr ? "Güncelle" : "Update"}</button>
          </form>
        </Panel>
      )}
      <Panel title={tr ? "İki adımlı doğrulama" : "Two-factor"}>
        <button
          className={buttonClass}
          type="button"
          onClick={async () => {
            const result = await api<{ secret: string }>("/two-factor/enable", { method: "POST" });
            if (result.ok) {
              setSecret(result.data.secret);
            }
          }}
        >
          {tr ? "Kurulumu başlat" : "Start setup"}
        </button>
        {secret && <p className="mt-3 text-sm">{tr ? "Gizli anahtar" : "Secret"}: {secret}</p>}
        <form
          className="mt-4 flex gap-2"
          onSubmit={async (event) => {
            event.preventDefault();
            const form = new FormData(event.currentTarget);
            const result = await api("/two-factor/confirm", { method: "POST", body: JSON.stringify({ code: form.get("code") }) });
            setMessage(result.ok ? (tr ? "2FA açıldı." : "2FA enabled.") : result.error);
          }}
        >
          <input className={inputClass} name="code" placeholder="123456" />
          <button className={buttonClass} type="submit">{tr ? "Onayla" : "Confirm"}</button>
        </form>
      </Panel>
      <Panel title={tr ? "Oturumlar" : "Sessions"}>
        <ul className="text-sm">
          {sessions.map((session) => (
            <li key={session.id} className="flex justify-between py-2">
              <span>{session.ip_address} · {session.user_agent} {session.current ? (tr ? "(bu cihaz)" : "(this device)") : ""}</span>
              {!session.current && (
                <button type="button" className="underline" onClick={() => api(`/sessions/${session.id}`, { method: "DELETE" }).then(load)}>
                  {tr ? "Kapat" : "Revoke"}
                </button>
              )}
            </li>
          ))}
        </ul>
        <button
          className="mt-4 text-sm text-warn underline"
          type="button"
          onClick={async () => {
            if (!confirm(tr ? "Çalışma alanı silme sürecine alınsın mı?" : "Schedule workspace deletion?")) {
              return;
            }
            const result = await api("/workspace", { method: "DELETE" });
            setMessage(result.ok ? (tr ? "Silme süreci başladı." : "Deletion scheduled.") : result.error);
          }}
        >
          {tr ? "Çalışma alanını sil" : "Delete workspace"}
        </button>
      </Panel>
    </div>
  );
}
