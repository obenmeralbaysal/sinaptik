"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { api } from "./api";
import type { User } from "./types";

type MeResponse = { user: User };

export function useMe(enabled = true) {
  return useQuery({
    queryKey: ["me"],
    enabled,
    retry: false,
    staleTime: 60_000,
    queryFn: async () => {
      const { data } = await api.get<MeResponse>("/api/me");
      return data.user;
    },
  });
}

export function useLogin() {
  const qc = useQueryClient();
  const router = useRouter();
  return useMutation({
    mutationFn: async (vars: { email: string; password: string; remember?: boolean }) => {
      const { data } = await api.post<MeResponse>("/api/login", vars);
      return data.user;
    },
    onSuccess: (user) => {
      qc.setQueryData(["me"], user);
      const slug = user.current_organization?.slug ?? user.organizations[0]?.slug;
      router.push(slug ? `/t/${slug}/dashboard` : "/");
    },
  });
}

export function useRegister() {
  const qc = useQueryClient();
  const router = useRouter();
  return useMutation({
    mutationFn: async (vars: {
      name: string;
      email: string;
      password: string;
      password_confirmation: string;
      organization_name?: string;
    }) => {
      const { data } = await api.post<MeResponse>("/api/register", vars);
      return data.user;
    },
    onSuccess: (user) => {
      qc.setQueryData(["me"], user);
      const slug = user.current_organization?.slug ?? user.organizations[0]?.slug;
      router.push(slug ? `/t/${slug}/dashboard` : "/");
    },
  });
}

export function useLogout() {
  const qc = useQueryClient();
  const router = useRouter();
  return useMutation({
    mutationFn: async () => {
      await api.post("/api/logout");
    },
    onSuccess: () => {
      qc.setQueryData(["me"], null);
      qc.clear();
      router.push("/login");
    },
  });
}

export function useSwitchOrganization() {
  const qc = useQueryClient();
  const router = useRouter();
  return useMutation({
    mutationFn: async (slug: string) => {
      const { data } = await api.post<MeResponse>("/api/me/switch-organization", { slug });
      return data.user;
    },
    onSuccess: (user, slug) => {
      qc.setQueryData(["me"], user);
      router.push(`/t/${slug}/dashboard`);
    },
  });
}
