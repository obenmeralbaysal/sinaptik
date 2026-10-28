"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { FormEvent, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Logo, NavIcon } from "@/components/chrome";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { GoogleConnectCard } from "@/components/google-connect";
import { api, extractError, setWorkspaceSlug } from "@/lib/api";
import { useLogout, useMe } from "@/lib/auth";
import type { Site } from "@/lib/types";
import { AxiosError } from "axios";

export default function WorkspacePage() {
  const params = useParams<{ slug: string }>();
  const slug = params.slug;
  setWorkspaceSlug(slug);
  const me = useMe();
  const logout = useLogout();
  const qc = useQueryClient();
  const [name, setName] = useState("");
  const [domain, setDomain] = useState("");

  const sites = useQuery({
    queryKey: ["sites", slug],
    enabled: me.isSuccess,
    retry: false,
    queryFn: async () => {
      const { data } = await api.get<{ data: Site[] }>("/api/sites");
      return data.data;
    },
  });

  const create = useMutation({
    mutationFn: async () => {
      await api.post("/api/sites", { name, domain });
    },
    onSuccess: async () => {
      setName("");
      setDomain("");
      await qc.invalidateQueries({ queryKey: ["sites", slug] });
    },
    onError: (error) => toast.error(extractError(error)),
  });

  function onSubmit(event: FormEvent) {
    event.preventDefault();
    create.mutate();
  }

  const subscriptionMissing = sites.error instanceof AxiosError && sites.error.response?.status === 402;

  return (
    <main className="min-h-screen">
      <header className="flex items-center justify-between border-b border-line bg-canvas px-8 py-5">
        <div>
          <Link href="/" aria-label="Sinaptik Analytics">
            <Logo />
          </Link>
          <p className="mt-1 inline-flex items-center gap-1.5 text-sm text-slate">
            <NavIcon name="sites" />
            {me.data?.current_workspace?.name ?? slug}
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="ghost" asChild>
            <Link href="/pricing" className="inline-flex items-center gap-2">
              <NavIcon name="pricing" />
              Fiyatlandırma
            </Link>
          </Button>
          <Button variant="outline" onClick={() => logout.mutate()} disabled={logout.isPending}>
            Çıkış
          </Button>
        </div>
      </header>
      <section className="mx-auto max-w-3xl px-6 py-10 space-y-6">
        <h1 className="text-2xl font-semibold">Siteler</h1>
        <GoogleConnectCard />
        {subscriptionMissing && (
          <Card>
            <CardHeader>
              <CardTitle>Aktif abonelik yok</CardTitle>
            </CardHeader>
            <CardContent className="text-sm text-muted-foreground">
              Varsayılan plana düşülmez. Site eklemek için Sinapp aboneliği gerekir.{" "}
              <Link href="/pricing" className="underline">
                Planları inceleyin
              </Link>
              .
            </CardContent>
          </Card>
        )}
        <form onSubmit={onSubmit} className="grid gap-3 sm:grid-cols-[1fr_1fr_auto] items-end">
          <div className="space-y-2">
            <Label htmlFor="site-name">Ad</Label>
            <Input id="site-name" required value={name} onChange={(e) => setName(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label htmlFor="domain">Alan adı</Label>
            <Input id="domain" required value={domain} onChange={(e) => setDomain(e.target.value)} placeholder="ornek.com" />
          </div>
          <Button type="submit" disabled={create.isPending}>
            Ekle
          </Button>
        </form>
        <ul className="divide-y border rounded-lg">
          {sites.data?.map((site) => (
            <li key={site.id} className="flex items-center justify-between px-4 py-3">
              <div>
                <p className="font-medium">{site.name}</p>
                <p className="text-sm text-muted-foreground">{site.domain}</p>
              </div>
            </li>
          ))}
          {sites.isSuccess && sites.data.length === 0 && (
            <li className="px-4 py-6 text-sm text-muted-foreground">Henüz site yok.</li>
          )}
        </ul>
      </section>
    </main>
  );
}
