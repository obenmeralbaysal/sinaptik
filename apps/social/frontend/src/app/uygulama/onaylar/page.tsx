"use client";

import { useEffect, useState } from "react";
import { Panel, buttonClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Post = { id: number; body: string | null; status: string };

export default function ApprovalsPage() {
  const { tr } = useLocale();
  const [posts, setPosts] = useState<Post[]>([]);

  async function load() {
    const result = await api<{ posts: Post[] }>("/posts?status=pending_approval");
    if (result.ok) {
      setPosts(result.data.posts);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function act(id: number, decision: "approve" | "reject") {
    await api(`/posts/${id}/${decision}`, { method: "POST", body: JSON.stringify({}) });
    await load();
  }

  return (
    <Panel title={tr ? "Onay kuyruğu" : "Approval queue"}>
      <ul className="space-y-3">
        {posts.map((post) => (
          <li key={post.id} className="flex items-center justify-between rounded-2xl bg-cream p-4">
            <p>{post.body || `#${post.id}`}</p>
            <div className="flex gap-2">
              <button className={buttonClass} type="button" onClick={() => act(post.id, "approve")}>{tr ? "Onayla" : "Approve"}</button>
              <button className="rounded-full border border-ink px-4 py-2 text-sm" type="button" onClick={() => act(post.id, "reject")}>{tr ? "Reddet" : "Reject"}</button>
            </div>
          </li>
        ))}
        {posts.length === 0 && <li className="text-sm text-muted">{tr ? "Bekleyen onay yok." : "Nothing waiting."}</li>}
      </ul>
    </Panel>
  );
}
