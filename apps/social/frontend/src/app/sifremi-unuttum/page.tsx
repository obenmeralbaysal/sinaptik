"use client";

import { FormEvent, useState } from "react";
import { SiteHeader, buttonClass, Field, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

export default function ForgotPage() {
  const { tr } = useLocale();
  const [message, setMessage] = useState<string | null>(null);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const result = await api("/forgot-password", {
      method: "POST",
      body: JSON.stringify({ email: form.get("email") }),
    });
    setMessage(result.ok ? (tr ? "Sıfırlama bağlantısı gönderildi." : "Reset link sent.") : result.error);
  }

  return (
    <>
      <SiteHeader />
      <main className="mx-auto max-w-md px-6">
        <h1 className="text-4xl">{tr ? "Şifre sıfırlama" : "Reset password"}</h1>
        <form onSubmit={onSubmit} className="mt-6 space-y-4 rounded-3xl border border-line bg-white p-6">
          <Field label={tr ? "E-posta" : "Email"}><input className={inputClass} name="email" type="email" required /></Field>
          {message && <p className="text-sm">{message}</p>}
          <button className={buttonClass} type="submit">{tr ? "Gönder" : "Send"}</button>
        </form>
      </main>
    </>
  );
}
