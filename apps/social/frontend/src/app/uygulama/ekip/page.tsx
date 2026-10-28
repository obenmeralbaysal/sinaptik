"use client";

import { FormEvent, useEffect, useState } from "react";
import { Panel, buttonClass, Field, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Member = { user_id: number; role: string; ads_write: boolean; user?: { name: string; email: string } };
type Invite = { id: number; email: string; role: string; expires_at: string };

export default function TeamPage() {
  const { tr } = useLocale();
  const [members, setMembers] = useState<Member[]>([]);
  const [invites, setInvites] = useState<Invite[]>([]);
  const [token, setToken] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function load() {
    const [memberResult, inviteResult] = await Promise.all([
      api<{ members: Member[] }>("/members"),
      api<{ invites: Invite[] }>("/invites"),
    ]);
    if (memberResult.ok) {
      setMembers(memberResult.data.members);
    }
    if (inviteResult.ok) {
      setInvites(inviteResult.data.invites);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function invite(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const result = await api<{ token: string }>("/invites", {
      method: "POST",
      body: JSON.stringify({
        email: form.get("email"),
        role: form.get("role"),
        ads_write: form.get("ads_write") === "on",
      }),
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
    <Panel title={tr ? "Ekip" : "Team"}>
      <form onSubmit={invite} className="grid max-w-xl gap-3 md:grid-cols-2">
        <Field label={tr ? "E-posta" : "Email"}><input className={inputClass} name="email" type="email" required /></Field>
        <Field label={tr ? "Rol" : "Role"}>
          <select className={inputClass} name="role" defaultValue="editor">
            {["admin", "editor", "approver", "viewer"].map((role) => <option key={role}>{role}</option>)}
          </select>
        </Field>
        <label className="flex items-center gap-2 text-sm"><input name="ads_write" type="checkbox" /> ads.write</label>
        <button className={buttonClass} type="submit">{tr ? "Davet et" : "Invite"}</button>
      </form>
      {error && <p className="mt-3 text-sm text-warn">{error}</p>}
      {token && <p className="mt-3 break-all text-sm">{tr ? "Tek kullanımlık belirteç" : "Single-use token"}: {token}</p>}
      <h2 className="mt-8 text-xl">{tr ? "Üyeler" : "Members"}</h2>
      <ul className="mt-2 text-sm">
        {members.map((member) => (
          <li key={member.user_id} className="py-1">{member.user?.name || member.user_id} · {member.role}</li>
        ))}
      </ul>
      <h2 className="mt-6 text-xl">{tr ? "Açık davetler" : "Open invites"}</h2>
      <ul className="mt-2 text-sm">
        {invites.map((invite) => (
          <li key={invite.id} className="flex justify-between py-1">
            <span>{invite.email} · {invite.role}</span>
            <button type="button" className="underline" onClick={() => api(`/invites/${invite.id}`, { method: "DELETE" }).then(load)}>{tr ? "İptal" : "Revoke"}</button>
          </li>
        ))}
      </ul>
    </Panel>
  );
}
