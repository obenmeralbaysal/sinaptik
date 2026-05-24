"use client";

import { useParams, useRouter } from "next/navigation";
import { useEffect } from "react";
import { Sidebar } from "@/components/sidebar";
import { TopBar } from "@/components/topbar";
import { setTenantSlug } from "@/lib/api";
import { useMe } from "@/lib/auth";

export default function TenantLayout({ children }: { children: React.ReactNode }) {
  const params = useParams<{ slug: string }>();
  const slug = params.slug;
  const router = useRouter();
  const { data: user, isLoading, isError } = useMe();

  useEffect(() => {
    setTenantSlug(slug);
    return () => setTenantSlug(null);
  }, [slug]);

  useEffect(() => {
    if (isError) router.replace("/login");
  }, [isError, router]);

  useEffect(() => {
    if (!user) return;
    const member = user.organizations.find((o) => o.slug === slug);
    if (!member) {
      const fallback = user.organizations[0]?.slug;
      router.replace(fallback ? `/t/${fallback}/dashboard` : "/login");
    }
  }, [user, slug, router]);

  if (isLoading || !user) {
    return (
      <div className="flex items-center justify-center min-h-screen text-sm text-muted-foreground">
        Loading workspace...
      </div>
    );
  }

  if (!user.organizations.find((o) => o.slug === slug)) {
    return null;
  }

  return (
    <div className="flex min-h-screen">
      <Sidebar slug={slug} />
      <div className="flex flex-col flex-1">
        <TopBar />
        <main className="flex-1 p-6 overflow-auto">{children}</main>
      </div>
    </div>
  );
}
