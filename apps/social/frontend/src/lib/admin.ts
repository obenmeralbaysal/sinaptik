"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { api } from "./api";
import type { Plan } from "./types";

type Stats = {
  users: number;
  organizations: number;
  social_accounts: number;
  posts_total: number;
  posts_pending: number;
  posts_failed: number;
};

export function useAdminStats() {
  return useQuery({
    queryKey: ["admin", "stats"],
    queryFn: async () => {
      const { data } = await api.get<{ data: Stats }>("/api/admin/stats");
      return data.data;
    },
  });
}

export function useAdminUsers() {
  return useQuery({
    queryKey: ["admin", "users"],
    queryFn: async () => {
      const { data } = await api.get<{ data: { data: Array<{ id: number; name: string; email: string; organizations_count: number; created_at: string }> } }>(
        "/api/admin/users",
      );
      return data.data.data ?? [];
    },
  });
}

export function useAdminPlans() {
  return useQuery({
    queryKey: ["admin", "plans"],
    queryFn: async () => {
      const { data } = await api.get<{ data: Plan[] }>("/api/admin/plans");
      return data.data;
    },
  });
}

export function useUpsertPlan() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (vars: Partial<Plan> & { id?: number }) => {
      if (vars.id) {
        const { data } = await api.patch<{ data: Plan }>(`/api/admin/plans/${vars.id}`, vars);
        return data.data;
      }
      const { data } = await api.post<{ data: Plan }>("/api/admin/plans", vars);
      return data.data;
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ["admin", "plans"] }),
  });
}

export function useDeletePlan() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: number) => {
      await api.delete(`/api/admin/plans/${id}`);
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ["admin", "plans"] }),
  });
}
