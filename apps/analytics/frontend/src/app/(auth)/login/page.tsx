"use client";

import Link from "next/link";
import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { extractError } from "@/lib/api";
import { useLogin } from "@/lib/auth";

export default function LoginPage() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const login = useLogin();

  return (
    <form
      onSubmit={(event) => {
        event.preventDefault();
        login.mutate(
          { email, password },
          { onError: (error) => toast.error(extractError(error)) },
        );
      }}
      className="space-y-5"
    >
      <div className="space-y-1">
        <h1 className="text-2xl font-semibold">Tekrar hoş geldiniz</h1>
        <p className="text-sm text-muted-foreground">Çalışma alanınıza giriş yapın.</p>
      </div>
      <div className="space-y-2">
        <Label htmlFor="email">E-posta</Label>
        <Input id="email" type="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
      </div>
      <div className="space-y-2">
        <Label htmlFor="password">Parola</Label>
        <Input id="password" type="password" required value={password} onChange={(e) => setPassword(e.target.value)} />
      </div>
      <Button type="submit" className="w-full" disabled={login.isPending}>
        {login.isPending ? "Giriş yapılıyor..." : "Giriş yap"}
      </Button>
      <p className="text-sm text-muted-foreground text-center">
        Hesabınız yok mu?{" "}
        <Link href="/register" className="font-medium underline underline-offset-4">
          Kayıt olun
        </Link>
      </p>
    </form>
  );
}
