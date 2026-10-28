"use client";

import { useEffect, useState } from "react";
import { SiteFooter, SiteHeader } from "@/components/chrome";
import { api } from "@/lib/api";

export function LegalView({ slug }: { slug: string }) {
  const [title, setTitle] = useState(slug);
  const [body, setBody] = useState("");

  useEffect(() => {
    api<{ page: { title: string; body_markdown: string } }>(`/legal/${slug}`).then((result) => {
      if (result.ok) {
        setTitle(result.data.page.title);
        setBody(result.data.page.body_markdown);
      } else {
        setBody(result.error);
      }
    });
  }, [slug]);

  return (
    <>
      <SiteHeader />
      <main className="mx-auto max-w-3xl px-6">
        <h1 className="text-5xl">{title}</h1>
        <article className="mt-8 whitespace-pre-wrap text-base leading-7">{body}</article>
      </main>
      <SiteFooter />
    </>
  );
}
