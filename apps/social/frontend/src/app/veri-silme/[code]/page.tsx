"use client";

import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { SiteHeader } from "@/components/chrome";
import { api } from "@/lib/api";

export default function DeletionStatusPage() {
  const params = useParams<{ code: string }>();
  const [status, setStatus] = useState("...");

  useEffect(() => {
    api<{ status: string; confirmation_code: string }>(`/meta/data-deletion/${params.code}`).then((result) => {
      setStatus(result.ok ? `${result.data.confirmation_code}: ${result.data.status}` : result.error);
    });
  }, [params.code]);

  return (
    <>
      <SiteHeader />
      <main className="mx-auto max-w-xl px-6">
        <h1 className="text-4xl">Veri silme durumu</h1>
        <p className="mt-4">{status}</p>
      </main>
    </>
  );
}
