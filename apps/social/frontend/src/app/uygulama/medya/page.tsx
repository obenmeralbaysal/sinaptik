"use client";

import { FormEvent, useEffect, useState } from "react";
import { Panel, buttonClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

type Media = { id: number; original_name: string; mime: string | null; size_bytes: number };

export default function MediaPage() {
  const { tr } = useLocale();
  const [media, setMedia] = useState<Media[]>([]);
  const [error, setError] = useState<string | null>(null);

  async function load() {
    const result = await api<{ media: Media[] }>("/media");
    if (result.ok) {
      setMedia(result.data.media);
    }
  }

  useEffect(() => {
    load();
  }, []);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const result = await api("/media", { method: "POST", body: form });
    if (!result.ok) {
      setError(result.error);
      return;
    }
    setError(null);
    await load();
  }

  return (
    <Panel title={tr ? "Medya kütüphanesi" : "Media library"}>
      <form onSubmit={onSubmit} className="flex items-center gap-3">
        <input name="file" type="file" required />
        <button className={buttonClass} type="submit">{tr ? "Yükle" : "Upload"}</button>
      </form>
      {error && <p className="mt-3 text-sm text-warn">{error}</p>}
      <ul className="mt-6 divide-y divide-line text-sm">
        {media.map((item) => (
          <li key={item.id} className="flex justify-between py-2">
            <span>{item.original_name}</span>
            <span className="text-muted">{item.mime} · {Math.ceil(item.size_bytes / 1024)} KB</span>
          </li>
        ))}
      </ul>
    </Panel>
  );
}
