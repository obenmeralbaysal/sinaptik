"use client";

import { AppFrame } from "@/components/chrome";

export default function UygulamaLayout({ children }: { children: React.ReactNode }) {
  return <AppFrame>{children}</AppFrame>;
}
