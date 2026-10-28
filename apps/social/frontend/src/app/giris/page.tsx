"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useEffect, useState } from "react";
import { SiteHeader } from "@/components/chrome";
import { buttonClass, Field, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api } from "@/lib/api";

export default function LoginPage() {
  const { tr } = useLocale();
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);
  const [twoFactor, setTwoFactor] = useState(false);
  const [pending, setPending] = useState(false);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      const result = await api<{ driver: string; login_url: string | null }>("/auth/identity");
      if (!cancelled && result.ok && result.data.driver === "oidc" && result.data.login_url) {
        window.location.assign(result.data.login_url);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPending(true);
    const form = new FormData(event.currentTarget);
    if (twoFactor) {
      const result = await api("/two-factor/challenge", {
        method: "POST",
        body: JSON.stringify({ code: String(form.get("code")) }),
      });
      setPending(false);
      if (!result.ok) {
        setError(result.error);
        return;
      }
      router.push("/uygulama");
      return;
    }
    const result = await api<{ two_factor?: boolean }>("/login", {
      method: "POST",
      body: JSON.stringify({ email: form.get("email"), password: form.get("password") }),
    });
    setPending(false);
    if (!result.ok) {
      setError(result.error);
      return;
    }
    if (result.data.two_factor) {
      setTwoFactor(true);
      return;
    }
    router.push("/uygulama");
  }

  return (
    <>
      <SiteHeader />
      <main className="mx-auto max-w-md px-6">
        <h1 className="text-4xl">{twoFactor ? (tr ? "Doğrulama kodu" : "Verification code") : tr ? "Giriş" : "Sign in"}</h1>
        <form onSubmit={onSubmit} className="mt-6 space-y-4 rounded-3xl border border-line bg-white p-6">
          {!twoFactor && (
            <>
              <Field label={tr ? "E-posta" : "Email"}>
                <input className={inputClass} name="email" type="email" required />
              </Field>
              <Field label={tr ? "Şifre" : "Password"}>
                <input className={inputClass} name="password" type="password" required />
              </Field>
            </>
          )}
          {twoFactor && (
            <Field label={tr ? "Uygulamadaki 6 hane" : "6 digits from the app"}>
              <input className={inputClass} name="code" inputMode="numeric" required />
            </Field>
          )}
          {error && <p className="text-sm text-warn">{error}</p>}
          <button className={buttonClass} disabled={pending} type="submit">
            {tr ? "Devam" : "Continue"}
          </button>
        </form>
        <p className="mt-4 text-sm">
          <Link href="/sifremi-unuttum" className="underline">{tr ? "Şifremi unuttum" : "Forgot password"}</Link>
        </p>
      </main>
    </>
  );
}
