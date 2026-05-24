"use client";

import {
  CalendarClock,
  Home,
  ListChecks,
  Plug,
  Repeat,
  Settings,
  Sparkles,
  Target,
  Rss,
} from "lucide-react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";

const items = [
  { href: "dashboard", label: "Dashboard", icon: Home },
  { href: "accounts", label: "X Accounts", icon: Plug },
  { href: "posts", label: "Posts", icon: CalendarClock },
  { href: "targets", label: "Targets", icon: Target },
  { href: "routines", label: "Routines", icon: Sparkles },
  { href: "xml-sources", label: "XML Sources", icon: Rss },
  { href: "settings", label: "Settings", icon: Settings },
];

export function Sidebar({ slug }: { slug: string }) {
  const pathname = usePathname();
  return (
    <aside className="hidden md:flex flex-col w-60 border-r bg-card/40 px-3 py-5">
      <div className="px-2 mb-6">
        <Link href="/" className="font-semibold">
          Sinapp Social
        </Link>
        <p className="text-xs text-muted-foreground mt-1">/t/{slug}</p>
      </div>
      <nav className="flex flex-col gap-1">
        {items.map((it) => {
          const href = `/t/${slug}/${it.href}`;
          const active = pathname.startsWith(href);
          return (
            <Link
              key={it.href}
              href={href}
              className={cn(
                "flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors",
                active
                  ? "bg-accent text-accent-foreground font-medium"
                  : "text-muted-foreground hover:bg-accent hover:text-accent-foreground",
              )}
            >
              <it.icon className="h-4 w-4" />
              {it.label}
            </Link>
          );
        })}
      </nav>
    </aside>
  );
}
