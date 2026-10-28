"use client";

import { FormEvent, useEffect, useState } from "react";
import { Panel, buttonClass, Field, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api, money } from "@/lib/api";

type Campaign = { id: number; name: string; status: string; ad_sets?: { id: number; name: string; daily_budget_minor: number | null; status: string }[] };
type Proposal = { id: number; status: string; kind: string; payload: Record<string, unknown> };

export default function AdsPage() {
  const { tr } = useLocale();
  const [campaigns, setCampaigns] = useState<Campaign[]>([]);
  const [proposals, setProposals] = useState<Proposal[]>([]);
  const [message, setMessage] = useState<string | null>(null);

  async function load() {
    const [campaignResult, proposalResult] = await Promise.all([
      api<{ campaigns: Campaign[] }>("/campaigns"),
      api<{ proposals: Proposal[] }>("/ads/proposals"),
    ]);
    if (campaignResult.ok) {
      setCampaigns(campaignResult.data.campaigns);
    }
    if (proposalResult.ok) {
      setProposals(proposalResult.data.proposals);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function propose(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const result = await api("/ads/proposals", {
      method: "POST",
      body: JSON.stringify({
        ad_set_id: Number(form.get("ad_set_id")),
        kind: "status",
        status: form.get("status"),
      }),
    });
    setMessage(result.ok ? (tr ? "Öneri oluşturuldu." : "Proposal created.") : result.error);
    await load();
  }

  async function decide(id: number, action: "approve" | "reject" | "apply") {
    await api(`/ads/proposals/${id}/${action}`, { method: "POST", body: JSON.stringify({}) });
    await load();
  }

  const adSets = campaigns.flatMap((campaign) => campaign.ad_sets || []);

  return (
    <Panel title={tr ? "Reklamlar" : "Ads"}>
      <p className="mb-4 text-sm text-muted">
        {tr ? "Değişiklikler öneri olarak kaydedilir. Uygulama, onaydan sonra ve tavanların içinde yapılır." : "Changes are stored as proposals and applied only after approval, inside the caps."}
      </p>
      <form onSubmit={propose} className="mb-6 flex flex-wrap items-end gap-3">
        <Field label="Ad set">
          <select className={inputClass} name="ad_set_id">
            {adSets.map((adSet) => (
              <option key={adSet.id} value={adSet.id}>{adSet.name} · {money(adSet.daily_budget_minor)}</option>
            ))}
          </select>
        </Field>
        <Field label={tr ? "Durum" : "Status"}>
          <select className={inputClass} name="status" defaultValue="PAUSED">
            <option value="PAUSED">PAUSED</option>
            <option value="ACTIVE">ACTIVE</option>
          </select>
        </Field>
        <button className={buttonClass} type="submit">{tr ? "Öner" : "Propose"}</button>
      </form>
      {message && <p className="mb-4 text-sm">{message}</p>}
      <ul className="space-y-2">
        {proposals.map((proposal) => (
          <li key={proposal.id} className="flex items-center justify-between rounded-2xl bg-cream px-4 py-3 text-sm">
            <span>#{proposal.id} · {proposal.kind} · {proposal.status}</span>
            <span className="flex gap-2">
              <button type="button" onClick={() => decide(proposal.id, "approve")}>{tr ? "Onayla" : "Approve"}</button>
              <button type="button" onClick={() => decide(proposal.id, "reject")}>{tr ? "Reddet" : "Reject"}</button>
              <button type="button" onClick={() => decide(proposal.id, "apply")}>{tr ? "Uygula" : "Apply"}</button>
            </span>
          </li>
        ))}
      </ul>
    </Panel>
  );
}
