"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import { SiteHeader, buttonClass, Field, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

export default function RegisterPage() {
  const { tr } = useLocale();
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const result = await api("/register", {
      method: "POST",
      body: JSON.stringify({
        name: form.get("name"),
        email: form.get("email"),
        password: form.get("password"),
        workspace_name: form.get("workspace_name"),
        locale: tr ? "tr" : "en",
      }),
    });
    if (!result.ok) {
      setError(result.error);
      return;
    }
    router.push("/uygulama/baslangic");
  }

  return (
    <>
      <SiteHeader />
      <main className="mx-auto max-w-md px-6">
        <h1 className="text-4xl">{tr ? "Hesap aç" : "Create account"}</h1>
        <p className="mt-2 text-sm text-muted">{tr ? "E-postanızı doğruladıktan sonra 14 günlük Pro denemesi başlar." : "The 14-day Pro trial starts after you verify your email."}</p>
        <form onSubmit={onSubmit} className="mt-6 space-y-4 rounded-3xl border border-line bg-white p-6">
          <Field label={tr ? "Ad" : "Name"}><input className={inputClass} name="name" required /></Field>
          <Field label={tr ? "E-posta" : "Email"}><input className={inputClass} name="email" type="email" required /></Field>
          <Field label={tr ? "Şifre" : "Password"}><input className={inputClass} name="password" type="password" required minLength={8} /></Field>
          <Field label={tr ? "Çalışma alanı" : "Workspace"}><input className={inputClass} name="workspace_name" required /></Field>
          {error && <p className="text-sm text-warn">{error}</p>}
          <button className={buttonClass} type="submit">{tr ? "Kayıt ol" : "Register"}</button>
        </form>
      </main>
    </>
  );
}
