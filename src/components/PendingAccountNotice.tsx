'use client';

import Link from 'next/link';
import { CheckCircle2, Clock3 } from 'lucide-react';
import { LocaleSwitcher } from '@/components/LocaleSwitcher';
import { t } from '@/lib/locale';
import { useLocale } from '@/lib/locale-context';

export function PendingAccountNotice() {
  const locale = useLocale();
  return <main className="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-8 text-white sm:py-10">
    <section className="w-full max-w-lg rounded-3xl border border-white/10 bg-slate-900 p-5 text-center shadow-2xl sm:p-8">
      <div className="mb-4 flex justify-end"><LocaleSwitcher /></div>
      <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-400/10 text-amber-300"><Clock3 className="h-8 w-8" /></div>
      <h1 className="mt-6 text-2xl font-bold">{t(locale, 'auth.pendingTitle')}</h1>
      <p className="mt-3 text-sm leading-7 text-slate-300">{t(locale, 'auth.pendingText')}</p>
      <div className="mt-6 flex items-start justify-center gap-2 rounded-xl bg-emerald-500/10 p-4 text-left text-sm leading-6 text-emerald-200 sm:items-center"><CheckCircle2 className="mt-0.5 h-5 w-5 shrink-0 sm:mt-0" /><span>{t(locale, 'auth.pendingReady')}</span></div>
      <Link href="/login" className="mt-7 inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold hover:bg-emerald-500">{t(locale, 'auth.pendingBack')}</Link>
    </section>
  </main>;
}
