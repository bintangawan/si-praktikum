'use client';

import Link from 'next/link';
import { useLocale } from '@/lib/locale-context';
import { t } from '@/lib/locale';
import { appName } from '@/lib/app-config';

export default function NotFound() {
  const locale = useLocale();
  return <main className="grid min-h-screen place-items-center bg-gray-50 px-5 text-center text-gray-900">
    <section className="max-w-md">
      <p className="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">{appName}</p>
      <p className="mt-5 text-6xl font-extrabold tracking-tight text-emerald-900">404</p>
      <h1 className="mt-3 text-2xl font-bold">{t(locale, 'page.notFoundTitle')}</h1>
      <p className="mt-3 text-sm leading-6 text-slate-600">{t(locale, 'page.notFoundText')}</p>
      <Link href="/" className="mt-7 inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800">{t(locale, 'page.backHome')}</Link>
    </section>
  </main>;
}
