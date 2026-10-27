import type { Metadata } from "next";
import { Plus_Jakarta_Sans } from "next/font/google";
import { Providers } from "@/components/providers";
import "./globals.css";

const jakarta = Plus_Jakarta_Sans({
  subsets: ["latin", "latin-ext"],
  weight: ["400", "700"],
  variable: "--font-jakarta",
  display: "swap",
});

export const metadata: Metadata = {
  title: "Sinaptik Analytics",
  description: "SEO, GEO ve site analitiği",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="tr" suppressHydrationWarning className={`${jakarta.variable} h-full antialiased`}>
      <body className={`${jakarta.className} min-h-full flex flex-col bg-canvas text-slate`}>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
