"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { api } from "./api";
import type { Post, Routine, SocialAccount, Target, XmlSource } from "./types";

// Social accounts
export function useSocialAccounts() {
  return useQuery({
    queryKey: ["social-accounts"],
    queryFn: async () => {
      const { data } = await api.get<{ data: SocialAccount[] }>("/api/social-accounts");
      return data.data;
    },
  });
}

export function useConnectX() {
  return useMutation({
    mutationFn: async () => {
      const { data } = await api.post<{ auth_url: string }>("/api/social-accounts/x/connect");
      return data.auth_url;
    },
  });
}

export function useDeleteSocialAccount() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/api/social-accounts/${id}`);
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ["social-accounts"] }),
  });
}

// Posts
export function usePosts(status?: string) {
  return useQuery({
    queryKey: ["posts", status ?? "all"],
    queryFn: async () => {
      const { data } = await api.get<{ data: { data: Post[] } }>("/api/posts", {
        params: status ? { status } : undefined,
      });
      return data.data.data ?? [];
    },
  });
}

export function useCreatePost() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (vars: {
      social_account_id: number;
      content: string;
      scheduled_at?: string | null;
      delivery?: "draft" | "publish" | "schedule";
    }) => {
      const { data } = await api.post<{ data: Post }>("/api/posts", vars);
      return data.data;
    },
    onSettled: () => qc.invalidateQueries({ queryKey: ["posts"] }),
  });
}

export function useSendPostNow() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      const { data } = await api.post<{ data: Post }>(`/api/posts/${id}/send-now`);
      return data.data;
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ["posts"] }),
  });
}

export function useDeletePost() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/api/posts/${id}`);
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ["posts"] }),
  });
}

// Targets
export function useTargets() {
  return useQuery({
    queryKey: ["targets"],
    queryFn: async () => {
      const { data } = await api.get<{ data: Target[] }>("/api/targets");
      return data.data;
    },
  });
}

export function useCreateTarget() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (vars: Partial<Target>) => {
      const { data } = await api.post<{ data: Target }>("/api/targets", vars);
      return data.data;
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ["targets"] }),
  });
}

export function useDeleteTarget() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/api/targets/${id}`);
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ["targets"] }),
  });
}

// Routines
export function useRoutines() {
  return useQuery({
    queryKey: ["routines"],
    queryFn: async () => {
      const { data } = await api.get<{ data: Routine[] }>("/api/routines");
      return data.data;
    },
  });
}

export function useGenerateRoutine() {
  return useMutation({
    mutationFn: async (vars: {
      persona?: string;
      tone?: string;
      language?: string;
      topic: string;
      count?: number;
      model?: string;
    }) => {
      const { data } = await api.post<{ data: string[] }>("/api/routines/generate", vars);
      return data.data;
    },
  });
}

export function useStoreRoutine() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (vars: Record<string, unknown>) => {
      const { data } = await api.post<{ data: Routine }>("/api/routines", vars);
      return data.data;
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["routines"] });
      qc.invalidateQueries({ queryKey: ["posts"] });
    },
  });
}

// AI Settings (per-tenant)
export type AiSettings = {
  driver: "gemini" | "openai" | null;
  has_gemini_key: boolean;
  has_openai_key: boolean;
  default_text_model: string | null;
  default_image_model: string | null;
};

export function useAiSettings(slug: string) {
  return useQuery({
    queryKey: ["ai-settings", slug],
    enabled: !!slug,
    queryFn: async () => {
      const { data } = await api.get<{ data: AiSettings }>(`/api/orgs/${slug}/ai-settings`);
      return data.data;
    },
  });
}

export function useUpdateAiSettings(slug: string) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (vars: {
      driver?: "gemini" | "openai" | null;
      gemini_api_key?: string;
      openai_api_key?: string;
      default_text_model?: string | null;
      default_image_model?: string | null;
      clear_gemini_key?: boolean;
      clear_openai_key?: boolean;
    }) => {
      const { data } = await api.patch<{ data: AiSettings }>(`/api/orgs/${slug}/ai-settings`, vars);
      return data.data;
    },
    onSuccess: (data) => {
      qc.setQueryData(["ai-settings", slug], data);
    },
  });
}

// XML sources
export function useXmlSources() {
  return useQuery({
    queryKey: ["xml-sources"],
    queryFn: async () => {
      const { data } = await api.get<{ data: XmlSource[] }>("/api/xml-sources");
      return data.data;
    },
  });
}

export function useCreateXmlSource() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (vars: Partial<XmlSource>) => {
      const { data } = await api.post<{ data: XmlSource }>("/api/xml-sources", vars);
      return data.data;
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ["xml-sources"] }),
  });
}

export function useDeleteXmlSource() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/api/xml-sources/${id}`);
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ["xml-sources"] }),
  });
}
