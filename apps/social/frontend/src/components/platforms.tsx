const platforms = [
  { id: "youtube", label: "YouTube", color: "#FF0033" },
  { id: "tiktok", label: "TikTok", color: "#111111" },
  { id: "instagram", label: "Instagram", color: "#E1306C" },
  { id: "linkedin", label: "LinkedIn", color: "#0A66C2" },
  { id: "x", label: "X", color: "#0F172A" },
  { id: "facebook", label: "Facebook", color: "#1877F2" },
] as const;

export type PlatformId = (typeof platforms)[number]["id"];

export function platformMatches(platform: string, id: PlatformId): boolean {
  const value = platform.toLowerCase();
  if (id === "facebook") {
    return value.includes("facebook");
  }
  if (id === "instagram") {
    return value.includes("instagram");
  }
  if (id === "x") {
    return value === "x" || value.includes("twitter");
  }
  return value.includes(id);
}

export function PlatformGlyph({ id, className = "h-5 w-5" }: { id: PlatformId; className?: string }) {
  const common = { className, viewBox: "0 0 24 24", fill: "currentColor", "aria-hidden": true } as const;
  if (id === "youtube") {
    return (
      <svg {...common}>
        <path d="M23 12.2s0-3.2-.4-4.6c-.2-.9-.9-1.6-1.8-1.8C19.2 5.4 12 5.4 12 5.4s-7.2 0-8.8.4c-.9.2-1.6.9-1.8 1.8C1 9 1 12.2 1 12.2s0 3.2.4 4.6c.2.9.9 1.6 1.8 1.8 1.6.4 8.8.4 8.8.4s7.2 0 8.8-.4c.9-.2 1.6-.9 1.8-1.8.4-1.4.4-4.6.4-4.6zM9.8 15.5v-6.6l6.2 3.3-6.2 3.3z" />
      </svg>
    );
  }
  if (id === "tiktok") {
    return (
      <svg {...common}>
        <path d="M14.5 3c.4 2.4 1.8 4.2 4.2 4.6v2.4c-1.5 0-2.9-.5-4.2-1.3v6.6c0 3.4-2.7 6.2-6.2 6.2S2 18.7 2 15.3 4.8 9 8.3 9c.4 0 .8 0 1.2.1v2.6c-.4-.2-.8-.3-1.2-.3-2 0-3.6 1.6-3.6 3.6s1.6 3.6 3.6 3.6 3.6-1.6 3.6-3.6V3h2.6z" />
      </svg>
    );
  }
  if (id === "instagram") {
    return (
      <svg {...common}>
        <path d="M12 7.2A4.8 4.8 0 1 0 12 16.8 4.8 4.8 0 0 0 12 7.2zm0 7.9a3.1 3.1 0 1 1 0-6.2 3.1 3.1 0 0 1 0 6.2zM17.4 6.9a1.1 1.1 0 1 1-2.2 0 1.1 1.1 0 0 1 2.2 0zM12 2.2c-2.5 0-2.8 0-3.8.1-1 .1-1.7.2-2.3.5-.6.2-1.1.6-1.6 1.1-.5.5-.9 1-1.1 1.6-.3.6-.4 1.3-.5 2.3-.1 1-.1 1.3-.1 3.8s0 2.8.1 3.8c.1 1 .2 1.7.5 2.3.2.6.6 1.1 1.1 1.6.5.5 1 .9 1.6 1.1.6.3 1.3.4 2.3.5 1 .1 1.3.1 3.8.1s2.8 0 3.8-.1c1-.1 1.7-.2 2.3-.5.6-.2 1.1-.6 1.6-1.1.5-.5.9-1 1.1-1.6.3-.6.4-1.3.5-2.3.1-1 .1-1.3.1-3.8s0-2.8-.1-3.8c-.1-1-.2-1.7-.5-2.3-.2-.6-.6-1.1-1.1-1.6-.5-.5-1-.9-1.6-1.1-.6-.3-1.3-.4-2.3-.5-1-.1-1.3-.1-3.8-.1zm0 1.8c2.4 0 2.7 0 3.7.1.9.1 1.4.2 1.7.3.4.2.7.4 1 .7.3.3.5.6.7 1 .1.3.2.8.3 1.7.1 1 .1 1.3.1 3.7s0 2.7-.1 3.7c-.1.9-.2 1.4-.3 1.7-.2.4-.4.7-.7 1-.3.3-.6.5-1 .7-.3.1-.8.2-1.7.3-1 .1-1.3.1-3.7.1s-2.7 0-3.7-.1c-.9-.1-1.4-.2-1.7-.3-.4-.2-.7-.4-1-.7-.3-.3-.5-.6-.7-1-.1-.3-.2-.8-.3-1.7-.1-1-.1-1.3-.1-3.7s0-2.7.1-3.7c.1-.9.2-1.4.3-1.7.2-.4.4-.7.7-1 .3-.3.6-.5 1-.7.3-.1.8-.2 1.7-.3 1-.1 1.3-.1 3.7-.1z" />
      </svg>
    );
  }
  if (id === "linkedin") {
    return (
      <svg {...common}>
        <path d="M4.7 9H1.2v13h3.5V9zM2.9 2C1.7 2 .8 2.9.8 4.1S1.7 6.2 2.9 6.2 5 5.3 5 4.1 4.1 2 2.9 2zM22.8 13.4c0-3.6-1.9-5.3-4.5-5.3-2.1 0-3 1.1-3.5 1.9V9H11v13h3.5v-7.2c0-1.9.4-3.7 2.7-3.7s2.4 2.1 2.4 3.8V22H23v-8.6z" />
      </svg>
    );
  }
  if (id === "facebook") {
    return (
      <svg {...common}>
        <path d="M14.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.5 1.6-1.5H18V5.1C17.7 5 16.6 5 15.4 5c-2.6 0-4.4 1.6-4.4 4.5V11H8v3h3v8h3.5z" />
      </svg>
    );
  }
  return (
    <svg {...common}>
      <path d="M17.6 3H20.5l-6.4 7.3L21.7 21h-5.5l-4.3-5.6L7 21H4l6.8-7.8L2.5 3h5.6l3.9 5.1L17.6 3zm-1 16.2h1.6L7.5 4.7H5.8l10.8 14.5z" />
    </svg>
  );
}

export function PlatformIcon({
  id,
  connected = false,
  size = "md",
}: {
  id: PlatformId;
  connected?: boolean;
  size?: "sm" | "md";
}) {
  const item = platforms.find((platform) => platform.id === id);
  const box = size === "sm" ? "h-9 w-9" : "h-11 w-11";
  return (
    <span
      className={`inline-flex ${box} items-center justify-center rounded-2xl text-white shadow-sm ${connected ? "" : "opacity-80"}`}
      style={{ backgroundColor: item?.color }}
      title={item?.label}
    >
      <PlatformGlyph id={id} className={size === "sm" ? "h-4 w-4" : "h-5 w-5"} />
    </span>
  );
}

export function PlatformRow({
  connected = [],
  size = "md",
}: {
  connected?: string[];
  size?: "sm" | "md";
}) {
  return (
    <div className="flex flex-wrap gap-2">
      {platforms.map((platform) => (
        <PlatformIcon
          key={platform.id}
          id={platform.id}
          size={size}
          connected={connected.some((value) => platformMatches(value, platform.id))}
        />
      ))}
    </div>
  );
}

export { platforms };
