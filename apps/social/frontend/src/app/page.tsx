"use client";

import Link from "next/link";
import { SiteFooter, SiteHeader } from "@/components/chrome";
import { useLocale } from "@/components/providers";
import { PlatformRow } from "@/components/platforms";

const copy = {
  tr: {
    headline: "Tüm sosyal medya hesapların tek panelde. Planla, yönet, büyüt.",
    sub: "Daha fazlası, tek bir yer. YouTube, TikTok, Instagram, LinkedIn, X (Twitter), Facebook ve daha fazlasını tek bir panelde yönetin. İçeriklerinizi planlayın, yayınlayın, performansınızı takip edin. İsterseniz yapay zeka desteğiyle etkileyici içerikler de oluşturun. Sinaptik Social, sosyal medyayı senin için kolaylaştırır.",
    trial: "14 gün Pro, kart yok",
    plans: "Paketleri gör",
    cards: [
      ["Tek panel", "YouTube, TikTok, Instagram, LinkedIn, X ve Facebook aynı çalışma alanında."],
      ["Planla ve yayınla", "Takvim, onay ve zamanlanmış gönderiler. Durumlar teal ile görünür."],
      ["Yapay zeka", "İndigo vurgular, içerik üretiminde yapay zeka desteğinin yerini tutar."],
    ],
  },
  en: {
    headline: "Every social account in one panel. Plan, manage, grow.",
    sub: "More, in one place. Manage YouTube, TikTok, Instagram, LinkedIn, X (Twitter), Facebook and more from a single panel. Plan, publish and track performance. Create standout posts with AI when you want to. Sinaptik Social makes social media easier for you.",
    trial: "14-day Pro, no card",
    plans: "See plans",
    cards: [
      ["One panel", "YouTube, TikTok, Instagram, LinkedIn, X and Facebook in the same workspace."],
      ["Plan and publish", "Calendar, approval and scheduled posts, with teal status badges."],
      ["AI assist", "Indigo highlights mark where AI-assisted creation will live."],
    ],
  },
};

export default function HomePage() {
  const { locale } = useLocale();
  const text = copy[locale];

  return (
    <div className="bg-navy text-[#F8FAFC]">
      <div className="relative overflow-hidden">
        <svg className="pointer-events-none absolute -right-16 -top-24 h-[140%] w-[46%] min-w-[280px]" viewBox="0 0 480 900" aria-hidden>
          <defs>
            <linearGradient id="hero-swoosh" x1="40" y1="0" x2="440" y2="860" gradientUnits="userSpaceOnUse">
              <stop stopColor="#0F172A" />
              <stop offset="0.28" stopColor="#6366F1" />
              <stop offset="0.68" stopColor="#14B8A6" />
              <stop offset="1" stopColor="#A3E635" />
            </linearGradient>
          </defs>
          <path
            d="M120 0c80 70 10 160 90 250 90 100 20 170 110 280 70 80 120 150 160 370H480V0H120z"
            fill="url(#hero-swoosh)"
          />
        </svg>
        <SiteHeader tone="navy" />
        <main className="relative mx-auto grid max-w-6xl items-center gap-10 px-6 pb-20 pt-4 md:grid-cols-[1.1fr_0.9fr] md:pb-28">
          <h1 className="text-4xl leading-tight text-[#F8FAFC] md:text-5xl">{text.headline}</h1>
          <div>
            <p className="text-base leading-7 text-[#F8FAFC]/85">{text.sub}</p>
            <div className="mt-6">
              <PlatformRow />
            </div>
            <div className="mt-8 flex flex-wrap gap-3">
              <Link href="/kayit" className="rounded-2xl bg-indigo px-5 py-3 font-semibold text-[#F8FAFC] shadow-sm">
                {text.trial}
              </Link>
              <Link href="/fiyatlandirma" className="rounded-2xl border border-white/25 px-5 py-3 font-semibold text-[#F8FAFC]">
                {text.plans}
              </Link>
            </div>
          </div>
        </main>
      </div>
      <section className="bg-canvas text-slate">
        <div className="mx-auto grid max-w-6xl gap-4 px-6 py-16 md:grid-cols-3">
          {text.cards.map(([title, body]) => (
            <article key={title} className="rounded-3xl border border-line bg-white p-6 shadow-[0_12px_40px_rgba(15,23,42,0.06)]">
              <h2 className="text-2xl text-navy">{title}</h2>
              <p className="mt-3 text-sm leading-6 text-slate">{body}</p>
            </article>
          ))}
        </div>
        <SiteFooter />
      </section>
    </div>
  );
}
