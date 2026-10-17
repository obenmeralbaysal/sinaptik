"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { LocaleToggle, SiteFooter } from "@/components/chrome";
import { Logo } from "@/components/logo";
import { PlatformGlyph, platforms } from "@/components/platforms";
import { useLocale } from "@/components/providers";
import { api, money } from "@/lib/api";

type Plan = {
  code: string;
  name: string;
  description: string;
  price_monthly_minor: number;
  price_yearly_minor: number;
};

const copy = {
  tr: {
    kicker: "Sosyal medya ve reklam, tek çalışma alanı",
    headline: "Tüm sosyal medya hesapların tek panelde. Planla, yönet, büyüt.",
    sub: "Daha fazlası, tek bir yer. YouTube, TikTok, Instagram, LinkedIn, X (Twitter), Facebook ve daha fazlasını tek bir panelde yönetin. İçeriklerinizi planlayın, yayınlayın, performansınızı takip edin. İsterseniz yapay zeka desteğiyle etkileyici içerikler de oluşturun. Sinaptik Social, sosyal medyayı senin için kolaylaştırır.",
    trial: "14 gün ücretsiz deneme",
    trialNote: "Kart gerekmez",
    plans: "Paketleri gör",
    nav: ["Özellikler", "Akış", "Fiyatlar", "SSS"],
    featuresTitle: "Tek panelde yayın, reklam ve ölçüm",
    features: [
      ["Paylaşım ve takvim", "Gönderiyi yazın, görseli ekleyin, haftalık takvime bırakın. Onay ve zamanlama aynı yerde."],
      ["Reklam, onayla", "Bütçe değişiklikleri öneri olarak gelir. İnsan onaylar. Otomatik uygulama yalnızca sahip ve tavan içinde."],
      ["Yapay zeka içerik", "Metin taslağı ve varyasyon, indigo ile işaretli. Son söz sizde kalır."],
      ["MCP ve ajanlar", "Anahtarlı MCP sunucusu. Ajanlar okur, önerir; yayın ve reklam yine izninize bağlıdır."],
      ["Analitik", "Hesap sağlığı, gönderi durumu ve öne çıkan metrikler tek bakışta."],
    ],
    howTitle: "Nasıl çalışır",
    steps: [
      ["Çalışma alanı açın", "14 gün Pro deneme başlar. Kart istenmez."],
      ["Hesapları bağlayın", "Meta bağlantısı ve diğer kanallar aynı listede durur."],
      ["Planlayın, onaylayın", "Takvimden yayınlayın. Reklam bütçesi öneri olarak gelir, siz onaylarsınız."],
    ],
    priceTitle: "TL paketler",
    priceSub: "14 gün ücretsiz deneme, kart gerekmez. Ücretli yükseltme çalışma alanındaki faturalama ekranından, Sinapp ödeme merkezi üzerinden alınır.",
    trust: "Ödemeler Sinapp güvencesiyle alınır.",
    faqTitle: "Sorular",
    faqs: [
      ["Deneme bitince ne olur?", "Ödeme yoksa çalışma alanı salt okunur olur ve zamanlanmış gönderiler durur. Veri hemen silinmez."],
      ["Reklam bütçesi kendi kendine artar mı?", "Hayır. Varsayılan, öner ve onayla. Otomatik uygulama yalnızca sahip tarafından ve paket tavanı içinde açılır."],
      ["Kart bilgisi burada mı durur?", "Hayır. Tahsilat Sinapp ödeme merkezindedir (sinapp.com.tr). Bu uygulama kart saklamaz."],
      ["Ajanlar doğrudan yayınlar mı?", "MCP ile ajanlar taslak ve öneri üretir. Yayın ve bütçe değişikliği sizin onayınıza bağlıdır."],
    ],
    ctaTitle: "Sosyal medyayı tek yerden yönetin.",
    cta: "Denemeyi başlat",
    sign: "Giriş",
  },
  en: {
    kicker: "Social and ads, one workspace",
    headline: "Every social account in one panel. Plan, manage, grow.",
    sub: "More, in one place. Manage YouTube, TikTok, Instagram, LinkedIn, X (Twitter), Facebook and more from a single panel. Plan, publish and track performance. Create standout posts with AI when you want to. Sinaptik Social makes social media easier for you.",
    trial: "14-day free trial",
    trialNote: "No card",
    plans: "See plans",
    nav: ["Features", "Flow", "Pricing", "FAQ"],
    featuresTitle: "Publishing, ads and measurement in one panel",
    features: [
      ["Posts and calendar", "Write, attach media and drop it on the week. Approval and scheduling live together."],
      ["Ads, with approval", "Budget changes arrive as proposals. A person approves. Auto-apply is owner-only and inside caps."],
      ["AI content", "Drafts and variations, marked in indigo. You keep the last word."],
      ["MCP and agents", "A keyed MCP server. Agents read and propose; publishing and ads still need you."],
      ["Analytics", "Account health, post status and the metric that matters, in one glance."],
    ],
    howTitle: "How it works",
    steps: [
      ["Open a workspace", "The 14-day Pro trial starts. No card."],
      ["Connect accounts", "Meta and the other channels sit in one list."],
      ["Plan, then approve", "Publish from the calendar. Ad budgets arrive as proposals you approve."],
    ],
    priceTitle: "Prices in TRY",
    priceSub: "14-day free trial, no card. Paid upgrades go through billing in the workspace and the Sinapp payment hub.",
    trust: "Payments are taken with Sinapp.",
    faqTitle: "Questions",
    faqs: [
      ["What happens when the trial ends?", "Without payment the workspace becomes read-only and scheduled posts pause. Data is not deleted immediately."],
      ["Can ad budget change by itself?", "No. The default is propose and approve. Auto-apply is owner-only and inside the plan cap."],
      ["Do you store card numbers?", "No. Collection happens at the Sinapp hub (sinapp.com.tr). This app does not store cards."],
      ["Do agents publish on their own?", "MCP agents draft and propose. Publishing and budget changes still wait for you."],
    ],
    ctaTitle: "Run social from one place.",
    cta: "Start the trial",
    sign: "Sign in",
  },
};

const anchors = ["#ozellikler", "#akis", "#fiyatlar", "#sss"];

export default function HomePage() {
  const { locale, tr } = useLocale();
  const text = copy[locale];
  const [plans, setPlans] = useState<Plan[]>([]);

  useEffect(() => {
    api<{ plans: Plan[] }>("/plans").then((result) => {
      if (result.ok) {
        setPlans(result.data.plans);
      }
    });
  }, []);

  return (
    <div className="landing">
      <header className="sticky top-0 z-30 border-b border-white/10 bg-[#0F172A]/80 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4">
          <Link href="/" aria-label="Sinaptik Social">
            <Logo tone="on-navy" className="h-9 w-auto" />
          </Link>
          <nav className="hidden items-center gap-6 text-sm text-[#F8FAFC]/75 md:flex">
            {text.nav.map((label, index) => (
              <a key={label} href={anchors[index]} className="hover:text-[#F8FAFC]">
                {label}
              </a>
            ))}
          </nav>
          <div className="flex items-center gap-3 text-sm">
            <Link href="/giris" className="hidden text-[#F8FAFC]/80 sm:inline">
              {text.sign}
            </Link>
            <Link href="/kayit" className="rounded-2xl bg-indigo px-4 py-2 font-semibold text-[#F8FAFC]">
              {text.trial}
            </Link>
            <LocaleToggle onDark />
          </div>
        </div>
      </header>

      <section className="relative overflow-hidden">
        <div className="landing-grid pointer-events-none absolute inset-0" />
        <div className="aurora aurora-a pointer-events-none absolute -left-20 top-10 h-72 w-72 bg-indigo/50" />
        <div className="aurora aurora-b pointer-events-none absolute right-0 top-24 h-80 w-80 bg-teal/35" />
        <div className="aurora aurora-c pointer-events-none absolute bottom-0 left-1/3 h-64 w-64 bg-lime/25" />
        <div className="relative mx-auto grid max-w-6xl items-center gap-14 px-5 pb-20 pt-16 lg:grid-cols-[1.05fr_0.95fr] lg:pt-24">
          <div>
            <p className="text-sm font-semibold tracking-wide text-[#14B8A6]">{text.kicker}</p>
            <div className="mt-6 w-fit rounded-3xl bg-indigo/10 p-3 shadow-[0_0_80px_rgba(99,102,241,0.45)]">
              <Logo variant="mark" className="h-16 w-16" />
            </div>
            <h1 className="mt-6 max-w-xl text-4xl font-extrabold leading-[1.05] tracking-tight text-[#F8FAFC] sm:text-6xl">{text.headline}</h1>
            <p className="mt-6 max-w-xl text-base leading-7 text-[#F8FAFC]/75">{text.sub}</p>
            <div className="mt-8 flex flex-wrap items-center gap-3">
              <Link href="/kayit" className="rounded-2xl bg-indigo px-5 py-3 font-semibold text-[#F8FAFC] shadow-[0_10px_40px_rgba(99,102,241,0.35)]">
                {text.trial}
              </Link>
              <Link href="#fiyatlar" className="rounded-2xl border border-white/20 px-5 py-3 font-semibold text-[#F8FAFC]">
                {text.plans}
              </Link>
              <span className="text-sm text-[#A3E635]">{text.trialNote}</span>
            </div>
            <div className="mt-8 flex flex-wrap gap-2">
              {platforms.map((platform) => (
                <span key={platform.id} title={platform.label} className="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/5 text-[#F8FAFC] ring-1 ring-white/10">
                  <PlatformGlyph id={platform.id} />
                </span>
              ))}
            </div>
          </div>
          <div className="perspective-[1200px]">
            <div className="glass-mock rounded-[2rem] border border-white/15 bg-white/5 p-3 shadow-[0_40px_120px_rgba(15,23,42,0.45)] backdrop-blur-md">
              <div className="overflow-hidden rounded-[1.5rem] bg-[#F8FAFC] text-[#334155]">
                <div className="grid grid-cols-[92px_1fr]">
                  <div className="bg-[#0F172A] px-3 py-4 text-[#F8FAFC]">
                    <Logo variant="mark" className="h-7 w-7" />
                    <div className="mt-4 space-y-2 text-[10px] text-[#F8FAFC]/70">
                      <div className="rounded-xl bg-indigo px-2 py-1 text-[#F8FAFC]">{tr ? "Takvim" : "Calendar"}</div>
                      <div>{tr ? "Hesaplar" : "Accounts"}</div>
                      <div>{tr ? "Analitik" : "Analytics"}</div>
                    </div>
                  </div>
                  <div className="p-4">
                    <p className="text-xs font-semibold text-[#14B8A6]">Ada Studio</p>
                    <p className="text-lg font-bold text-[#0F172A]">{tr ? "İçerik Takvimi" : "Content calendar"}</p>
                    <div className="mt-3 grid grid-cols-7 gap-1">
                      {["P", "S", "Ç", "P", "C", "C", "P"].map((day, index) => (
                        <span key={`${day}-${index}`} className={`rounded-lg py-1 text-center text-[10px] ${index === 3 ? "bg-indigo text-[#F8FAFC]" : "bg-white text-[#0F172A]"}`}>
                          {day}
                        </span>
                      ))}
                    </div>
                    <div className="mt-3 rounded-2xl bg-white p-3 shadow-sm">
                      <div className="flex items-center justify-between">
                        <span className="text-sm font-semibold text-[#0F172A]">{tr ? "Sıradaki paylaşım" : "Up next"}</span>
                        <span className="rounded-full bg-teal px-2 py-0.5 text-[10px] font-semibold text-[#0F172A]">{tr ? "Zamanlandı" : "Scheduled"}</span>
                      </div>
                      <p className="mt-2 text-xs text-[#334155]">{tr ? "Bu haftanın öne çıkan gönderisi." : "This week's featured post."}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="ozellikler" className="mx-auto max-w-6xl px-5 py-20">
        <h2 className="reveal text-3xl font-bold text-[#F8FAFC] sm:text-4xl">{text.featuresTitle}</h2>
        <div className="mt-8 grid gap-4 md:grid-cols-2 lg:grid-cols-6">
          {text.features.map(([title, body], index) => (
            <article key={title} className={`reveal rounded-3xl border border-white/10 bg-white/5 p-6 ${index < 2 ? "lg:col-span-3" : "lg:col-span-2"}`}>
              <div className={`mb-4 h-1.5 w-10 rounded-full ${index === 1 ? "bg-teal" : index === 4 ? "bg-lime" : "bg-indigo"}`} />
              <h3 className="text-xl text-[#F8FAFC]">{title}</h3>
              <p className="mt-2 text-sm leading-6 text-[#F8FAFC]/70">{body}</p>
            </article>
          ))}
        </div>
      </section>

      <section id="akis" className="border-y border-white/10 bg-white/[0.03]">
        <div className="mx-auto grid max-w-6xl gap-6 px-5 py-20 md:grid-cols-3">
          <h2 className="text-3xl font-bold text-[#F8FAFC] md:col-span-3">{text.howTitle}</h2>
          {text.steps.map(([title, body], index) => (
            <article key={title} className="reveal rounded-3xl p-2">
              <p className="text-4xl font-extrabold text-[#6366F1]">0{index + 1}</p>
              <h3 className="mt-3 text-xl text-[#F8FAFC]">{title}</h3>
              <p className="mt-2 text-sm leading-6 text-[#F8FAFC]/70">{body}</p>
            </article>
          ))}
        </div>
      </section>

      <section id="fiyatlar" className="mx-auto max-w-6xl px-5 py-20">
        <h2 className="text-3xl font-bold text-[#F8FAFC] sm:text-4xl">{text.priceTitle}</h2>
        <p className="mt-3 max-w-2xl text-sm leading-6 text-[#F8FAFC]/70">{text.priceSub}</p>
        <p className="mt-3 text-sm font-semibold text-[#14B8A6]">
          {text.trust}{" "}
          <a href="https://sinapp.com.tr" className="underline">
            sinapp.com.tr
          </a>
        </p>
        <div className="mt-8 grid gap-4 md:grid-cols-3">
          {plans.map((plan) => (
            <article key={plan.code} className={`reveal rounded-3xl border p-6 ${plan.code === "pro" ? "border-indigo bg-indigo/10" : "border-white/10 bg-white/5"}`}>
              <h3 className="text-2xl text-[#F8FAFC]">{plan.name}</h3>
              <p className="mt-2 text-sm text-[#F8FAFC]/70">{plan.description}</p>
              <p className="mt-6 text-4xl font-bold text-[#F8FAFC]">{money(plan.price_monthly_minor)}</p>
              <p className="text-xs uppercase tracking-wide text-[#F8FAFC]/50">{tr ? "aylık" : "monthly"}</p>
              <Link href="/kayit" className={`mt-6 block rounded-2xl px-4 py-2 text-center font-semibold ${plan.code === "pro" ? "bg-teal text-[#0F172A]" : "bg-indigo text-[#F8FAFC]"}`}>
                {text.trial}
              </Link>
            </article>
          ))}
        </div>
      </section>

      <section id="sss" className="mx-auto max-w-3xl px-5 pb-16">
        <h2 className="text-3xl font-bold text-[#F8FAFC]">{text.faqTitle}</h2>
        <div className="mt-6 space-y-3">
          {text.faqs.map(([q, a]) => (
            <details key={q} className="rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
              <summary className="cursor-pointer font-semibold text-[#F8FAFC]">{q}</summary>
              <p className="mt-2 text-sm leading-6 text-[#F8FAFC]/70">{a}</p>
            </details>
          ))}
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-5 pb-20">
        <div className="rounded-[2rem] bg-indigo px-8 py-12 text-center shadow-[0_20px_80px_rgba(99,102,241,0.35)]">
          <h2 className="text-3xl font-bold text-[#F8FAFC] sm:text-4xl">{text.ctaTitle}</h2>
          <p className="mt-3 text-[#F8FAFC]/80">{text.trial}. {text.trialNote}.</p>
          <Link href="/kayit" className="mt-6 inline-block rounded-2xl bg-[#F8FAFC] px-6 py-3 font-semibold text-[#0F172A]">
            {text.cta}
          </Link>
        </div>
      </section>
      <SiteFooter tone="navy" />
    </div>
  );
}
