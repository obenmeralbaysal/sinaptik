"use client";

import axios, { AxiosError, type AxiosInstance } from "axios";

export const API_URL = process.env.NEXT_PUBLIC_API_URL?.trim() || "";

let cachedWorkspaceSlug: string | null = null;

export function setWorkspaceSlug(slug: string | null) {
  cachedWorkspaceSlug = slug;
}

export function getWorkspaceSlug(): string | null {
  if (cachedWorkspaceSlug) return cachedWorkspaceSlug;
  if (typeof window === "undefined") return null;
  const match = window.location.pathname.match(/^\/w\/([^/]+)/);
  return match ? decodeURIComponent(match[1]) : null;
}

let csrfPromise: Promise<void> | null = null;

async function ensureCsrfCookie(client: AxiosInstance): Promise<void> {
  if (csrfPromise) return csrfPromise;
  csrfPromise = client
    .get("/sanctum/csrf-cookie")
    .then(() => undefined)
    .catch(() => {
      csrfPromise = null;
    });
  return csrfPromise;
}

export const api: AxiosInstance = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: "application/json",
    "X-Requested-With": "XMLHttpRequest",
  },
});

api.interceptors.request.use(async (config) => {
  const slug = getWorkspaceSlug();
  if (slug) config.headers.set("X-Workspace-Slug", slug);

  const method = (config.method ?? "get").toLowerCase();
  if (["post", "put", "patch", "delete"].includes(method)) {
    await ensureCsrfCookie(api);
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401 && typeof window !== "undefined") {
      const path = window.location.pathname;
      if (!path.startsWith("/login") && !path.startsWith("/register")) {
        window.location.href = "/login";
      }
    }
    return Promise.reject(error);
  },
);

export type ApiError = {
  message: string;
  errors?: Record<string, string[]>;
};

export function extractError(error: unknown): string {
  if (error instanceof AxiosError) {
    const data = error.response?.data as ApiError | undefined;
    if (data?.errors) return Object.values(data.errors).flat().join(" ");
    return data?.message ?? error.message;
  }
  if (error instanceof Error) return error.message;
  return "Beklenmeyen hata";
}
