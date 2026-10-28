"use client";

import { FormEvent, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useMe } from "@/lib/auth";
import { api, extractError } from "@/lib/api";
import { GOOGLE_CONSENT_LINK_LABEL, GOOGLE_CONSENT_NOTICE_PATH } from "@/lib/google-consent";

type GoogleConnection = {
  text: string;
  link_label: string;
  text_version: string;
  notice_path: string;
  status: string;
  sync_status: string;
  connected: boolean;
  needs_reconsent: boolean;
};

export function GoogleConnectCard() {
  const qc = useQueryClient();
  const me = useMe();
  const [accepted, setAccepted] = useState(false);

  const connection = useQuery({
    queryKey: ["google-connection"],
    enabled: me.isSuccess,
    retry: false,
    queryFn: async () => {
      const { data } = await api.get<{ data: GoogleConnection }>("/api/google/connection");
      return data.data;
    },
  });

  const start = useMutation({
    mutationFn: async () => {
      const current = connection.data;
      if (!current || !accepted) return;
      const { data } = await api.post<{ data: { url: string } }>("/api/google/oauth/start", {
        consent: true,
        text_version: current.text_version,
      });
      if (data.data.url) window.location.assign(data.data.url);
    },
    onError: (error) => toast.error(extractError(error)),
  });

  const withdraw = useMutation({
    mutationFn: async () => {
      await api.delete("/api/google/connection");
    },
    onSuccess: async () => {
      setAccepted(false);
      await qc.invalidateQueries({ queryKey: ["google-connection"] });
    },
    onError: (error) => toast.error(extractError(error)),
  });

  const row = connection.data;
  const showConsent = !row?.connected || row.needs_reconsent;
  const noticePath = row?.notice_path || GOOGLE_CONSENT_NOTICE_PATH;
  const linkLabel = row?.link_label || GOOGLE_CONSENT_LINK_LABEL;

  function onConnect(event: FormEvent) {
    event.preventDefault();
    if (!accepted) return;
    start.mutate();
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Google hesabı</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4 text-sm">
        {row?.connected && !row.needs_reconsent && (
          <p>Bağlı. Senkron: {row.sync_status === "active" ? "açık" : row.sync_status}.</p>
        )}
        {row?.needs_reconsent && (
          <p>
            {row.sync_status === "paused"
              ? "Açık rıza metni değişti. Senkron duraklatıldı."
              : "Açık rıza metni değişti. Güncel rıza olmadan senkron çalışmaz."}
          </p>
        )}
        {showConsent && (
          <form onSubmit={onConnect} className="space-y-3">
            <div className="flex items-start gap-3">
              <input
                id="google-consent"
                type="checkbox"
                className="mt-1"
                checked={accepted}
                onChange={(event) => setAccepted(event.target.checked)}
              />
              <div>
                <label htmlFor="google-consent">{row?.text}</label>{" "}
                <a href={noticePath} className="underline">
                  {linkLabel}
                </a>
              </div>
            </div>
            <Button type="submit" disabled={!accepted || start.isPending || !row}>
              Bağla
            </Button>
          </form>
        )}
        {row?.connected && (
          <Button
            type="button"
            variant="outline"
            disabled={withdraw.isPending}
            onClick={() => withdraw.mutate()}
          >
            Bağlantıyı kaldır
          </Button>
        )}
      </CardContent>
    </Card>
  );
}
