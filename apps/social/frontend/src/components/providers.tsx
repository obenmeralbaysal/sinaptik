"use client";

import { createContext, useContext, useEffect, useMemo, useState } from "react";

export type Locale = "tr" | "en";

const LocaleContext = createContext<{
  locale: Locale;
  setLocale: (locale: Locale) => void;
  tr: boolean;
}>({ locale: "tr", setLocale: () => undefined, tr: true });

export function Providers({ children }: { children: React.ReactNode }) {
  const [locale, setLocaleState] = useState<Locale>("tr");

  useEffect(() => {
    const saved = localStorage.getItem("sinsocial.locale");
    if (saved === "en" || saved === "tr") {
      setLocaleState(saved);
    }
  }, []);

  useEffect(() => {
    document.documentElement.lang = locale;
  }, [locale]);

  const value = useMemo(
    () => ({
      locale,
      tr: locale === "tr",
      setLocale: (next: Locale) => {
        localStorage.setItem("sinsocial.locale", next);
        setLocaleState(next);
      },
    }),
    [locale],
  );

  return <LocaleContext.Provider value={value}>{children}</LocaleContext.Provider>;
}

export function useLocale() {
  return useContext(LocaleContext);
}
