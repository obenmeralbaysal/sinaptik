"use client";

import Link from "next/link";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { extractError } from "@/lib/api";
import { useRegister } from "@/lib/auth";

export default function RegisterPage() {
  const [name, setName] = useState("");
  const [workspaceName, setWorkspaceName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const register = useRegister();

  return (
    <form
      onSubmit={(event) => {
        event.preventDefault();
        register.mutate(
          {
            name,
            email,
            password,
            password_confirmation: password,
            workspace_name: workspaceName || undefined,
          },
          { onError: (error) => toast.error(extractError(error)) },
        );
      }}
      className="space-y-5"
    >
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold">Hesap açın</h1>
        <p className="text-sm text-muted-foreground">Yerel kimlik yansıması oluşturulur. Abonelik ayrıca gerekir.</p>
      </div>
      <div className="space-y-2">
        <Label htmlFor="name">Ad</Label>
        <Input id="name" required value={name} onChange={(e) => setName(e.target.value)} />
      </div>
      <div className="space-y-2">
        <Label htmlFor="workspace">Çalışma alanı</Label>
        <Input id="workspace" value={workspaceName} onChange={(e) => setWorkspaceName(e.target.value)} />
      </div>
      <div className="space-y-2">
        <Label htmlFor="email">E-posta</Label>
        <Input id="email" type="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
      </div>
      <div className="space-y-2">
        <Label htmlFor="password">Parola</Label>
        <Input id="password" type="password" required minLength={8} value={password} onChange={(e) => setPassword(e.target.value)} />
      </div>
      <Button type="submit" className="w-full" disabled={register.isPending}>
        {register.isPending ? "Oluşturuluyor..." : "Hesap aç"}
      </Button>
      <p className="text-sm text-muted-foreground text-center">
        Zaten hesabınız var mı?{" "}
        <Link href="/login" className="font-medium underline underline-offset-4">
          Giriş yapın
        </Link>
      </p>
    </form>
  );
}
