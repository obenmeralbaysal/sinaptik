"use client";

import { useEffect, useState } from "react";
import { Panel, buttonClass, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Comment = {
  id: number;
  platform: "facebook" | "instagram" | string;
  author_username: string | null;
  body: string | null;
  posted_at: string | null;
  hidden_at: string | null;
  parent_external_id: string | null;
};

export default function CommentsPage() {
  const { tr } = useLocale();
  const [comments, setComments] = useState<Comment[]>([]);
  const [drafts, setDrafts] = useState<Record<number, string>>({});
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function load() {
    const result = await api<{ comments: Comment[] }>("/comments");
    if (!result.ok) {
      setError(result.error);
      return;
    }
    setComments(result.data.comments);
  }

  useEffect(() => {
    load();
  }, []);

  async function sync() {
    setBusy(true);
    setError(null);
    const result = await api<{ comments: Comment[] }>("/comments/sync", { method: "POST" });
    setBusy(false);
    if (!result.ok) {
      setError(result.error);
      return;
    }
    setComments(result.data.comments);
  }

  async function reply(id: number) {
    const message = (drafts[id] || "").trim();
    if (!message) {
      return;
    }
    setError(null);
    const result = await api(`/comments/${id}/reply`, {
      method: "POST",
      body: JSON.stringify({ message }),
    });
    if (!result.ok) {
      setError(result.error);
      return;
    }
    setDrafts((current) => ({ ...current, [id]: "" }));
    await load();
  }

  async function hide(id: number) {
    const result = await api(`/comments/${id}/hide`, {
      method: "POST",
      body: JSON.stringify({ hidden: true }),
    });
    if (!result.ok) {
      setError(result.error);
      return;
    }
    await load();
  }

  function platformLabel(platform: string) {
    if (platform === "instagram") {
      return "Instagram";
    }
    if (platform === "facebook") {
      return "Facebook";
    }
    return platform;
  }

  return (
    <Panel title={tr ? "Yorumlar" : "Comments"}>
      <div className="mb-4 flex items-center gap-3">
        <button className={buttonClass} type="button" disabled={busy} onClick={sync}>
          {tr ? "Eşitle" : "Sync"}
        </button>
        <p className="text-sm text-slate">
          {tr
            ? "Yayınlanmış gönderilerin Facebook ve Instagram yorumları."
            : "Facebook and Instagram comments on published posts."}
        </p>
      </div>
      {error && <p className="mb-4 text-sm text-warn">{error}</p>}
      <ul className="space-y-3">
        {comments.map((comment) => (
          <li key={comment.id} className="rounded-2xl bg-cream p-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate">
              {platformLabel(comment.platform)}
              {comment.parent_external_id ? (tr ? " · yanıt" : " · reply") : ""}
              {comment.hidden_at ? (tr ? " · gizli" : " · hidden") : ""}
            </p>
            <p className="mt-1 font-medium text-navy">{comment.author_username || (tr ? "Yazar yok" : "No author")}</p>
            <p className="mt-1 text-sm">{comment.body}</p>
            <div className="mt-3 flex flex-wrap items-center gap-2">
              <input
                className={`${inputClass} max-w-md`}
                value={drafts[comment.id] || ""}
                placeholder={tr ? "Yanıt yazın" : "Write a reply"}
                onChange={(event) => setDrafts((current) => ({ ...current, [comment.id]: event.target.value }))}
              />
              <button className={buttonClass} type="button" onClick={() => reply(comment.id)}>
                {tr ? "Yanıtla" : "Reply"}
              </button>
              {!comment.hidden_at && (
                <button className="rounded-2xl border border-line px-4 py-2 text-sm" type="button" onClick={() => hide(comment.id)}>
                  {tr ? "Gizle" : "Hide"}
                </button>
              )}
            </div>
          </li>
        ))}
        {comments.length === 0 && (
          <li className="text-sm text-slate">{tr ? "Henüz yorum yok. Eşitle ile çekin." : "No comments yet. Sync to fetch them."}</li>
        )}
      </ul>
    </Panel>
  );
}
