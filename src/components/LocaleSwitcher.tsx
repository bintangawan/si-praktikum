'use client';

import { useState } from 'react';
import { showAppAlert } from '@/lib/alerts';
import { t, type Locale } from '@/lib/locale';
import { useChangeLocale, useLocale } from '@/lib/locale-context';

export function LocaleSwitcher({ className = '' }: { className?: string }) {
  const locale = useLocale();
  const changeLocale = useChangeLocale();
  const [pending, setPending] = useState(false);

  async function selectLocale(value: string) {
    if ((value !== 'id' && value !== 'en') || value === locale || pending) return;
    setPending(true);
    try {
      await changeLocale(value as Locale);
    } catch {
      const errorLocale = value as Locale;
      void showAppAlert(
        'error',
        errorLocale === 'en' ? 'Language was not changed' : 'Bahasa tidak berubah',
        errorLocale === 'en' ? 'Please try again.' : 'Silakan coba lagi.',
        errorLocale,
      );
    } finally {
      setPending(false);
    }
  }

  return (
    <label className={className}>
      <span className="sr-only">{t(locale, 'locale.label')}</span>
      <select
        value={locale}
        onChange={(event) => void selectLocale(event.target.value)}
        disabled={pending}
        aria-label={t(locale, 'locale.label')}
        className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm outline-none transition hover:border-emerald-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 disabled:opacity-60"
      >
        <option value="id">ID</option>
        <option value="en">EN</option>
      </select>
    </label>
  );
}
