"use client";

import axios, { AxiosError, type AxiosInstance } from "axios";

// Empty = same-origin subfolder routing (/api, /sanctum on the frontend domain).
export const API_URL = process.env.NEXT_PUBLIC_API_URL?.trim() || "";

let cachedTenantSlug: string | null = null;

export function setTenantSlug(slug: string | null) {
  cachedTenantSlug = slug;
}

export function getTenantSlug(): string | null {
  if (cachedTenantSlug) return cachedTenantSlug;
  if (typeof window === "undefined") return null;

  const m = window.location.pathname.match(/^\/t\/([^/]+)/);
  return m ? decodeURIComponent(m[1]) : null;
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
  const slug = getTenantSlug();
  if (slug) {
    config.headers.set("X-Organization-Slug", slug);
  }

  const method = (config.method ?? "get").toLowerCase();
  if (["post", "put", "patch", "delete"].includes(method)) {
    await ensureCsrfCookie(api);
  }
  return config;
});

api.interceptors.response.use(
  (r) => r,
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

export function extractError(e: unknown): string {
  if (e instanceof AxiosError) {
    const data = e.response?.data as ApiError | undefined;
    if (data?.errors) {
      return Object.values(data.errors).flat().join(" ");
    }
    return data?.message ?? e.message;
  }
  if (e instanceof Error) return e.message;
  return "Unexpected error";
}
