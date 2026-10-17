export type ApiOk<T> = { ok: true; status: number; data: T };
export type ApiErr = { ok: false; status: number; error: string };
export type ApiResult<T> = ApiOk<T> | ApiErr;

function xsrfToken(): string {
  const row = document.cookie.split("; ").find((part) => part.startsWith("XSRF-TOKEN="));
  return row ? decodeURIComponent(row.slice("XSRF-TOKEN=".length)) : "";
}

export function workspaceId(): string | null {
  return localStorage.getItem("sinsocial.workspace");
}

export function setWorkspaceId(id: number | string | null): void {
  if (id === null) {
    localStorage.removeItem("sinsocial.workspace");
    return;
  }
  localStorage.setItem("sinsocial.workspace", String(id));
}

export async function api<T>(path: string, init: RequestInit = {}): Promise<ApiResult<T>> {
  await fetch("/sanctum/csrf-cookie", { credentials: "include" });
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");
  headers.set("X-Requested-With", "XMLHttpRequest");
  headers.set("X-XSRF-TOKEN", xsrfToken());
  const workspace = workspaceId();
  if (workspace) {
    headers.set("X-Workspace-Id", workspace);
  }
  if (init.body && !(init.body instanceof FormData) && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  const response = await fetch(`/api${path}`, {
    ...init,
    headers,
    credentials: "include",
  });
  const data = (await response.json().catch(() => ({}))) as { message?: string };
  if (!response.ok) {
    return { ok: false, status: response.status, error: data.message || "İstek tamamlanamadı." };
  }
  return { ok: true, status: response.status, data: data as T };
}

export function money(minor: number | null | undefined): string {
  if (minor === null || minor === undefined) {
    return "—";
  }
  return new Intl.NumberFormat("tr-TR", { style: "currency", currency: "TRY" }).format(minor / 100);
}
