import Link from "next/link";
import { useId } from "react";

export function Logo({
  tone = "on-light",
  className = "h-9",
}: {
  tone?: "on-light" | "on-navy";
  className?: string;
}) {
  const raw = useId();
  const mask = `mark-${raw.replace(/:/g, "")}`;
  const word = tone === "on-navy" ? "text-canvas" : "text-navy";
  const sub = tone === "on-navy" ? "text-lime" : "text-indigo";

  return (
    <span className={`inline-flex items-center gap-2.5 ${className}`}>
      <svg viewBox="0 0 64 64" className="h-9 w-9 shrink-0" aria-hidden>
        <rect width="64" height="64" rx="14" fill="#0F172A" />
        <g transform="translate(5 5) scale(0.421875)">
          <mask id={mask}>
            <rect width="128" height="128" fill="white" />
            <path
              d="M 68.95 97.25 L 62.01 68.72 Q 56.00 44.00 78.90 55.08 L 102.81 66.65 Q 118.00 74.00 106.06 85.94 L 89.10 102.90 Q 74.00 118.00 68.95 97.25 Z"
              fill="black"
              stroke="black"
              strokeWidth="8"
              strokeLinejoin="round"
              strokeLinecap="round"
            />
          </mask>
          <path
            d="M 38.83 89.40 L 32.59 29.06 Q 30.00 4.00 52.65 15.03 L 90.09 33.27 Q 108.00 42.00 89.10 48.30 L 72.34 53.89 Q 66.00 56.00 63.65 62.25 L 52.80 91.20 Q 42.00 120.00 38.83 89.40 Z"
            fill="#6366F1"
            mask={`url(#${mask})`}
          />
          <path
            d="M 68.95 97.25 L 62.01 68.72 Q 56.00 44.00 78.90 55.08 L 102.81 66.65 Q 118.00 74.00 106.06 85.94 L 89.10 102.90 Q 74.00 118.00 68.95 97.25 Z"
            fill="#A3E635"
          />
        </g>
      </svg>
      <span className={`leading-tight ${word}`}>
        <span className="block text-sm font-bold tracking-tight">Sinaptik</span>
        <span className={`block text-xs font-semibold ${sub}`}>Analytics</span>
      </span>
    </span>
  );
}

export function NavIcon({ name }: { name: "home" | "pricing" | "sites" | "login" }) {
  const common = "h-4 w-4";
  if (name === "pricing") {
    return (
      <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
        <path d="M4 19V9M10 19V5M16 19v-7M22 19H2" />
      </svg>
    );
  }
  if (name === "sites") {
    return (
      <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
        <circle cx="12" cy="12" r="9" />
        <path d="M3 12h18M12 3c2.5 2.8 3.8 6 3.8 9s-1.3 6.2-3.8 9c-2.5-2.8-3.8-6-3.8-9s1.3-6.2 3.8-9z" />
      </svg>
    );
  }
  if (name === "login") {
    return (
      <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
        <path d="M10 7V5a2 2 0 0 1 2-2h7v18h-7a2 2 0 0 1-2-2v-2" />
        <path d="M15 12H3M6 9l-3 3 3 3" />
      </svg>
    );
  }
  return (
    <svg className={common} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
      <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z" />
    </svg>
  );
}

export function SiteHeader() {
  return (
    <header className="mx-auto flex max-w-6xl items-center justify-between px-6 py-6 text-navy">
      <Link href="/" aria-label="Sinaptik Analytics">
        <Logo />
      </Link>
      <nav className="flex items-center gap-4 text-sm font-medium">
        <Link href="/pricing" className="inline-flex items-center gap-2 text-slate hover:text-teal">
          <NavIcon name="pricing" />
          Fiyatlar
        </Link>
        <Link href="/login" className="inline-flex items-center gap-2">
          <NavIcon name="login" />
          Giriş
        </Link>
        <Link href="/register" className="rounded-2xl bg-indigo px-4 py-2 text-canvas shadow-sm">
          Hesap aç
        </Link>
      </nav>
    </header>
  );
}
