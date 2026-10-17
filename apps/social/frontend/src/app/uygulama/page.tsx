"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";
import { PlatformIcon, platforms, platformMatches } from "@/components/platforms";

type Snapshot = {
  workspace: { name: string; status: string };
  membership: { role: string };
};

type Post = {
  id: number;
  body: string | null;
  status: string;
  scheduled_at: string | null;
  post_type: string;
};

type Account = { id: number; platform: string; name: string; status: string };

const dayLabels = ["Pzt", "Sal", "Çar", "Per", "Cum", "Cmt", "Paz"];

function startOfWeek(date: Date): Date {
  const copy = new Date(date);
  const day = (copy.getDay() + 6) % 7;
  copy.setHours(0, 0, 0, 0);
  copy.setDate(copy.getDate() - day);
  return copy;
}

function sameDay(left: Date, right: Date): boolean {
  return left.getFullYear() === right.getFullYear() && left.getMonth() === right.getMonth() && left.getDate() === right.getDate();
}

function statusLabel(status: string, tr: boolean): string {
  if (status === "scheduled") {
    return tr ? "Zamanlandı" : "Scheduled";
  }
  if (status === "published") {
    return tr ? "Yayında" : "Published";
  }
  if (status === "failed") {
    return tr ? "Hata" : "Failed";
  }
  return tr ? "Taslak" : "Draft";
}

export default function DashboardPage() {
  const { tr } = useLocale();
  const [name, setName] = useState("");
  const [accounts, setAccounts] = useState<Account[]>([]);
  const [posts, setPosts] = useState<Post[]>([]);
  const [error, setError] = useState<string | null>(null);
  const today = useMemo(() => new Date(), []);
  const [selected, setSelected] = useState(today);
  const week = useMemo(() => {
    const start = startOfWeek(today);
    return Array.from({ length: 7 }, (_, index) => {
      const date = new Date(start);
      date.setDate(start.getDate() + index);
      return date;
    });
  }, [today]);

  useEffect(() => {
    api<Snapshot>("/workspace").then((result) => {
      if (result.ok) {
        setName(result.data.workspace.name);
      } else if (result.status !== 409) {
        setError(result.error);
      }
    });
    api<{ accounts: Account[] }>("/accounts").then((result) => {
      if (result.ok) {
        setAccounts(result.data.accounts);
      }
    });
    api<{ posts: Post[] }>("/posts").then((result) => {
      if (result.ok) {
        setPosts(result.data.posts);
      }
    });
  }, []);

  const dayPosts = posts.filter((post) => {
    if (!post.scheduled_at) {
      return sameDay(selected, today);
    }
    return sameDay(new Date(post.scheduled_at), selected);
  });
  const featured = dayPosts[0] ?? posts[0];
  const connected = accounts.map((account) => account.platform);
  const greeting = tr ? "Merhaba!" : "Hello!";

  return (
    <div className="space-y-6">
      <section className="-mx-4 -mt-5 bg-navy px-5 pb-6 pt-6 text-[#F8FAFC] md:hidden">
        <p className="text-3xl font-extrabold tracking-tight">{greeting}</p>
        <p className="mt-1 text-sm text-[#F8FAFC]/70">{tr ? "Bugünün içerik planı hazır." : "Today's content plan is ready."}</p>
        <div className="mt-5 flex gap-2">
          {platforms.map((platform) => (
            <PlatformIcon key={platform.id} id={platform.id} size="sm" connected={connected.some((value) => platformMatches(value, platform.id))} />
          ))}
        </div>
        <Link href="/uygulama/olustur" className="mt-5 flex items-center justify-center rounded-2xl bg-indigo px-4 py-3 text-sm font-semibold">
          + {tr ? "Yeni içerik oluştur" : "Create content"}
        </Link>
      </section>

      <div className="hidden items-end justify-between gap-4 md:flex">
        <div>
          <p className="text-sm font-semibold text-teal">{name || "Sinaptik Social"}</p>
          <h1 className="text-4xl text-navy">{tr ? "İçerik Takvimi" : "Content calendar"}</h1>
        </div>
        <Link href="/uygulama/olustur" className="rounded-2xl bg-indigo px-5 py-3 text-sm font-semibold text-[#F8FAFC] shadow-sm">
          + {tr ? "Yeni içerik oluştur" : "Create content"}
        </Link>
      </div>

      <div className="hidden gap-3 md:grid md:grid-cols-6">
        {platforms.map((platform) => {
          const linked = accounts.filter((account) => platformMatches(account.platform, platform.id));
          return (
            <article key={platform.id} className="rounded-3xl border border-line bg-white p-4 shadow-[0_12px_40px_rgba(15,23,42,0.05)]">
              <PlatformIcon id={platform.id} connected={linked.length > 0} />
              <p className="mt-3 text-sm font-semibold text-navy">{platform.label}</p>
              <p className="text-xs text-slate">{linked.length > 0 ? linked[0].name : tr ? "Bağlı değil" : "Not connected"}</p>
            </article>
          );
        })}
      </div>

      <div className="grid grid-cols-7 gap-2">
        {week.map((date, index) => {
          const active = sameDay(date, selected);
          return (
            <button
              key={date.toISOString()}
              type="button"
              onClick={() => setSelected(date)}
              className={`rounded-2xl px-1 py-3 text-center ${active ? "bg-indigo text-[#F8FAFC] shadow-sm" : "bg-white text-navy"}`}
            >
              <span className={`block text-[11px] font-semibold ${active ? "text-[#F8FAFC]/80" : "text-slate"}`}>{dayLabels[index]}</span>
              <span className="mt-1 block text-lg font-bold">{date.getDate()}</span>
            </button>
          );
        })}
      </div>

      <section className="rounded-3xl border border-line bg-white p-5 shadow-[0_12px_40px_rgba(15,23,42,0.06)]">
        <div className="flex items-center justify-between">
          <h2 className="text-xl text-navy">{tr ? "Sıradaki paylaşım" : "Up next"}</h2>
          {featured && <span className="rounded-full bg-teal px-3 py-1 text-xs font-semibold text-navy">{statusLabel(featured.status, tr)}</span>}
        </div>
        {featured ? (
          <div className="mt-4 flex gap-4">
            <div className="hidden h-24 w-32 shrink-0 rounded-2xl bg-navy sm:block" />
            <div>
              <p className="font-semibold text-navy">{featured.body || featured.post_type}</p>
              <p className="mt-1 text-sm text-slate">
                {featured.scheduled_at ? new Date(featured.scheduled_at).toLocaleString("tr-TR") : tr ? "Tarih seçilmedi" : "No date"}
              </p>
              <p className="mt-3 inline-flex rounded-full bg-lime/40 px-2 py-1 text-xs font-semibold text-navy">
                {tr ? "Öne çıkan metrik" : "Key metric"} · {posts.length}
              </p>
            </div>
          </div>
        ) : (
          <p className="mt-4 text-sm text-slate">{tr ? "Bu gün için paylaşım yok. Yeni içerik oluşturun." : "Nothing scheduled. Create a post."}</p>
        )}
        {error && <p className="mt-3 text-sm text-warn">{error}</p>}
      </section>

      <section className="md:hidden">
        <h2 className="text-lg text-navy">{tr ? "Bugünün Paylaşımları" : "Today's posts"}</h2>
        <ul className="mt-3 space-y-3">
          {(dayPosts.length > 0 ? dayPosts : posts).slice(0, 5).map((post) => (
            <li key={post.id} className="flex items-center gap-3 rounded-3xl bg-white p-3 shadow-sm">
              <span className="h-12 w-12 shrink-0 rounded-2xl bg-navy" />
              <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-semibold text-navy">{post.body || post.post_type}</p>
                <p className="text-xs text-slate">{post.scheduled_at ? new Date(post.scheduled_at).toLocaleTimeString("tr-TR", { hour: "2-digit", minute: "2-digit" }) : post.post_type}</p>
              </div>
              <span className="rounded-full bg-teal px-2 py-1 text-[10px] font-semibold text-navy">{statusLabel(post.status, tr)}</span>
            </li>
          ))}
          {posts.length === 0 && <li className="rounded-3xl bg-white p-4 text-sm text-slate">{tr ? "Henüz paylaşım yok." : "No posts yet."}</li>}
        </ul>
      </section>
    </div>
  );
}
