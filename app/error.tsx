'use client';

import { useEffect } from 'react';
import { appName } from '@/lib/app-config';
import { useLocale } from '@/lib/locale-context';
import { t } from '@/lib/locale';

export default function ErrorPage({ error, retry }: { error: Error & { digest?: string }; retry: () => void }) {
  const locale = useLocale();
  useEffect(() => {
    console.error('Application route failed.', error.digest ?? 'No error digest.');
  }, [error.digest]);

  return <main className="grid min-h-screen place-items-center bg-gray-50 px-5 text-center text-gray-900">
    <section className="max-w-md rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
      <p className="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">{appName}</p>
      <h1 className="mt-4 text-2xl font-bold">{t(locale, 'page.errorTitle')}</h1>
      <p className="mt-3 text-sm leading-6 text-slate-600">{t(locale, 'page.errorText')}</p>
      <button onClick={retry} className="mt-7 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800">{t(locale, 'page.retry')}</button>
    </section>
  </main>;
}
