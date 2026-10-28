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
  title: "Sinaptik Social",
  description: "Tüm sosyal medya hesapların tek panelde. Planla, yönet, büyüt.",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="tr">
      <body className={`${jakarta.variable} ${jakarta.className} antialiased`}>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
