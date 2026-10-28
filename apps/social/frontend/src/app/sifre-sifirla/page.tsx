"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { FormEvent, Suspense, useState } from "react";
import { SiteHeader, buttonClass, Field, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

function ResetForm() {
  const { tr } = useLocale();
  const params = useSearchParams();
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const password = String(form.get("password"));
    const result = await api("/reset-password", {
      method: "POST",
      body: JSON.stringify({
        email: params.get("email") || form.get("email"),
        token: params.get("token") || form.get("token"),
        password,
        password_confirmation: password,
      }),
    });
    if (!result.ok) {
      setError(result.error);
      return;
    }
    router.push("/giris");
  }

  return (
    <form onSubmit={onSubmit} className="mt-6 space-y-4 rounded-3xl border border-line bg-white p-6">
      <Field label={tr ? "E-posta" : "Email"}><input className={inputClass} name="email" type="email" defaultValue={params.get("email") || ""} required /></Field>
      <Field label="Token"><input className={inputClass} name="token" defaultValue={params.get("token") || ""} required /></Field>
      <Field label={tr ? "Yeni şifre" : "New password"}><input className={inputClass} name="password" type="password" required minLength={8} /></Field>
      {error && <p className="text-sm text-warn">{error}</p>}
      <button className={buttonClass} type="submit">{tr ? "Kaydet" : "Save"}</button>
    </form>
  );
}

export default function ResetPage() {
  const { tr } = useLocale();
  return (
    <>
      <SiteHeader />
      <main className="mx-auto max-w-md px-6">
        <h1 className="text-4xl">{tr ? "Yeni şifre" : "New password"}</h1>
        <Suspense>
          <ResetForm />
        </Suspense>
      </main>
    </>
  );
}
