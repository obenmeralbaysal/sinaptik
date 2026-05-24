"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect } from "react";
import { Button } from "@/components/ui/button";
import { useLogout, useMe } from "@/lib/auth";
import { cn } from "@/lib/utils";

const items = [
  { href: "/admin", label: "Stats" },
  { href: "/admin/users", label: "Users" },
  { href: "/admin/plans", label: "Plans" },
];

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const { data: user, isLoading, isError } = useMe();
  const logout = useLogout();

  useEffect(() => {
    if (isError) router.replace("/login");
  }, [isError, router]);

  useEffect(() => {
    if (user && !user.is_super_admin) router.replace("/");
  }, [user, router]);

  if (isLoading || !user) return null;
  if (!user.is_super_admin) return null;

  return (
    <div className="flex min-h-screen">
      <aside className="w-56 border-r p-4">
        <div className="mb-6 font-semibold">Sinapp Admin</div>
        <nav className="flex flex-col gap-1">
          {items.map((it) => (
            <Link
              key={it.href}
              href={it.href}
              className={cn(
                "rounded-md px-3 py-2 text-sm",
                pathname === it.href ? "bg-accent text-accent-foreground" : "text-muted-foreground hover:bg-accent",
              )}
            >
              {it.label}
            </Link>
          ))}
        </nav>
        <Button variant="ghost" size="sm" className="mt-6 w-full" onClick={() => logout.mutate()}>
          Logout
        </Button>
      </aside>
      <main className="flex-1 p-8">{children}</main>
    </div>
  );
}
