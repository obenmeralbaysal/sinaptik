/**
 * Sinaptik Social lockups. Paths live in frontend/public/brand and
 * backend/public/brand. See docs/BRAND.md before replacing them.
 */
const files = {
  mark: "/brand/mark.svg",
  horizontal: {
    "on-navy": "/brand/lockup-horizontal.svg",
    "on-light": "/brand/lockup-horizontal-light.svg",
  },
  stacked: {
    "on-navy": "/brand/lockup-stacked.svg",
    "on-light": "/brand/lockup-stacked-light.svg",
  },
} as const;

export function Logo({
  tone = "on-light",
  variant = "horizontal",
  className,
  markClassName,
}: {
  tone?: "on-light" | "on-navy";
  variant?: "mark" | "horizontal" | "stacked";
  className?: string;
  markClassName?: string;
}) {
  const src = variant === "mark" ? files.mark : files[variant][tone];
  const fallback = variant === "mark" ? markClassName || "h-9 w-9" : markClassName || "h-10 w-auto";

  return (
    // SVG lockup files are the source of truth shared with Filament and email.
    // eslint-disable-next-line @next/next/no-img-element
    <img src={src} alt="" className={className || fallback} />
  );
}

export function LogoMark({ className = "h-9 w-9" }: { className?: string }) {
  return <Logo variant="mark" className={className} />;
}
