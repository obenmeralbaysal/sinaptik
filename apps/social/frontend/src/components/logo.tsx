"use client";

import { useId } from "react";

/**
 * Placeholder mark. Replace this component and `src/app/icon.svg`
 * when the final logo arrives. See docs/BRAND.md.
 */
export function LogoMark({ className = "h-9 w-9" }: { className?: string }) {
  const raw = useId().replace(/:/g, "");
  const id = `sinaptik-s-${raw}`;

  return (
    <svg viewBox="0 0 64 64" className={className} aria-hidden="true">
      <defs>
        <linearGradient id={id} x1="8" y1="4" x2="56" y2="60" gradientUnits="userSpaceOnUse">
          <stop stopColor="#0F172A" />
          <stop offset="0.08" stopColor="#6366F1" />
          <stop offset="0.55" stopColor="#14B8A6" />
          <stop offset="1" stopColor="#A3E635" />
        </linearGradient>
      </defs>
      <path
        d="M46 16c0-7.4-7.2-12-15.2-12C20.2 4 12 9.2 12 17.6c0 6.2 4.4 9.6 12.8 12.2 9.2 2.8 16.2 5.2 16.2 12.6 0 7.2-6.6 12.6-15.4 12.6-8.2 0-14.2-4.2-16.6-10.4"
        fill="none"
        stroke={`url(#${id})`}
        strokeWidth="8"
        strokeLinecap="round"
      />
    </svg>
  );
}

export function Logo({
  tone = "on-light",
  markClassName = "h-9 w-9",
}: {
  tone?: "on-light" | "on-navy";
  markClassName?: string;
}) {
  const word = tone === "on-navy" ? "text-[#F8FAFC]" : "text-[#0F172A]";
  const sub = tone === "on-navy" ? "text-[#14B8A6]" : "text-[#14B8A6]";

  return (
    <span className="inline-flex items-center gap-2.5">
      <LogoMark className={markClassName} />
      <span className="leading-none">
        <span className={`block text-[1.05rem] font-extrabold tracking-tight ${word}`}>sinaptik</span>
        <span className={`block text-sm font-semibold tracking-tight ${sub}`}>social</span>
      </span>
    </span>
  );
}
