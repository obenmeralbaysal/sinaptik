"use client";

import { useEffect, useState } from "react";
import { Panel } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Post = { id: number; body: string | null; status: string; scheduled_at: string | null; post_type: string };

export default function CalendarPage() {
  const { tr } = useLocale();
  const [posts, setPosts] = useState<Post[]>([]);

  useEffect(() => {
    api<{ posts: Post[] }>("/posts").then((result) => {
      if (result.ok) {
        setPosts(result.data.posts);
      }
    });
  }, []);

  return (
    <Panel title={tr ? "Takvim" : "Calendar"}>
      <ul className="divide-y divide-line">
        {posts.map((post) => (
          <li key={post.id} className="grid grid-cols-[160px_1fr_auto] gap-3 py-3 text-sm">
            <span className="text-muted">{post.scheduled_at ? new Date(post.scheduled_at).toLocaleString("tr-TR") : (tr ? "Tarihsiz" : "Unscheduled")}</span>
            <span>{post.body || post.post_type}</span>
            <span className="rounded-full bg-cream px-2 py-1 text-xs">{post.status}</span>
          </li>
        ))}
        {posts.length === 0 && <li className="py-6 text-sm text-muted">{tr ? "Gönderi yok." : "No posts."}</li>}
      </ul>
    </Panel>
  );
}
