"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useRouter } from "next/navigation";
import { api } from "./api";
import type { User } from "./types";

type MeResponse = { user: User };

function homeFor(user: User): string {
  const slug = user.current_workspace?.slug ?? user.workspaces[0]?.slug;
  return slug ? `/w/${slug}` : "/";
}

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
    mutationFn: async (vars: { email: string; password: string }) => {
      const { data } = await api.post<MeResponse>("/api/login", vars);
      return data.user;
    },
    onSuccess: (user) => {
      qc.setQueryData(["me"], user);
      router.push(homeFor(user));
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
      workspace_name?: string;
    }) => {
      const { data } = await api.post<MeResponse>("/api/register", vars);
      return data.user;
    },
    onSuccess: (user) => {
      qc.setQueryData(["me"], user);
      router.push(homeFor(user));
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
      qc.clear();
      router.push("/login");
    },
  });
}
