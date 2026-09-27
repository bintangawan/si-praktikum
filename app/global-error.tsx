'use client';

import { useEffect, useSyncExternalStore } from 'react';
import { appName, defaultLocale, localeCookieName } from '@/lib/app-config';
import { getCookieLocale, t } from '@/lib/locale';
import './globals.css';

export default function GlobalError({ error, retry }: { error: Error & { digest?: string }; retry: () => void }) {
  const locale = useSyncExternalStore(() => () => {}, () => getCookieLocale(localeCookieName, defaultLocale), () => defaultLocale);
  useEffect(() => {
    console.error('Root application layout failed.', error.digest ?? 'No error digest.');
  }, [error.digest]);

  return <html lang={locale}><body className="bg-gray-50 font-sans antialiased text-gray-900">
    <main className="grid min-h-screen place-items-center px-5 text-center">
      <section className="max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <p className="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">{appName}</p>
        <h1 className="mt-4 text-2xl font-bold">{t(locale, 'page.globalErrorTitle')}</h1>
        <p className="mt-3 text-sm leading-6 text-slate-600">{t(locale, 'page.globalErrorText')}</p>
        <button onClick={retry} className="mt-7 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800">{t(locale, 'page.retry')}</button>
      </section>
    </main>
  </body></html>;
}
