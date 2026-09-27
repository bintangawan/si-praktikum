'use client';

import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react';
import { defaultLocale, localeCookieName } from './app-config';
import { getCookieLocale, type Locale } from './locale';

const LocaleContext = createContext<Locale>(defaultLocale);
const ChangeLocaleContext = createContext<(locale: Locale) => Promise<void>>(async () => {});

export function LocaleProvider({ children }: { children: ReactNode }) {
  const [locale, setLocale] = useState<Locale>(defaultLocale);

  useEffect(() => {
    const savedLocale = getCookieLocale(localeCookieName, defaultLocale);
    // The preference is browser-only; restore it after hydration to keep server markup stable.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setLocale(savedLocale);
    document.documentElement.lang = savedLocale;
  }, []);

  const changeLocale = useCallback(async (nextLocale: Locale) => {
    const response = await fetch('/api/locale', {
      method: 'POST',
      headers: { 'content-type': 'application/json' },
      body: JSON.stringify({ locale: nextLocale }),
    });
    if (!response.ok) throw new Error('Locale preference could not be saved.');
    setLocale(nextLocale);
    document.documentElement.lang = nextLocale;
  }, []);

  return (
    <LocaleContext.Provider value={locale}>
      <ChangeLocaleContext.Provider value={changeLocale}>{children}</ChangeLocaleContext.Provider>
    </LocaleContext.Provider>
  );
}

export function useLocale() {
  return useContext(LocaleContext);
}

export function useChangeLocale() {
  return useContext(ChangeLocaleContext);
}
