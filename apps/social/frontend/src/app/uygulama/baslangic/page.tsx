"use client";

import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import { Panel, buttonClass, Field, inputClass } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { api, setWorkspaceId } from "@/lib/api";

export default function OnboardingPage() {
  const { tr } = useLocale();
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const result = await api<{ workspace: { id: number } }>("/workspaces", {
      method: "POST",
      body: JSON.stringify({ name: form.get("name") }),
    });
    if (!result.ok) {
      setError(result.error);
      return;
    }
    setWorkspaceId(result.data.workspace.id);
    await api("/workspace/switch", { method: "POST" });
    router.push("/uygulama");
  }

  return (
    <Panel title={tr ? "Çalışma alanını aç" : "Open a workspace"}>
      <p className="mb-4 text-sm text-muted">
        {tr ? "E-posta doğrulaması tamamlanmadan çalışma alanı açılamaz. Gelen kutunuzdaki bağlantıyı kullanın." : "A workspace cannot be opened until the email is verified."}
      </p>
      <form onSubmit={onSubmit} className="max-w-md space-y-4">
        <Field label={tr ? "Firma veya marka" : "Company or brand"}>
          <input className={inputClass} name="name" required />
        </Field>
        {error && <p className="text-sm text-warn">{error}</p>}
        <button className={buttonClass} type="submit">{tr ? "Oluştur" : "Create"}</button>
      </form>
    </Panel>
  );
}
