'use client';

import Link from 'next/link';
import Image from 'next/image';
import { useState } from 'react';
import { ArrowRight, BookOpen, CheckCircle2, ClipboardCheck, GraduationCap, Menu, UsersRound, X } from 'lucide-react';
import { LocaleSwitcher } from '@/components/LocaleSwitcher';
import { t } from '@/lib/locale';
import { useLocale } from '@/lib/locale-context';

export function LandingPage({ signedIn, destination }: { signedIn: boolean; destination: string }) {
  const locale = useLocale();
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const closeMenu = () => setIsMenuOpen(false);

  return <main className="min-h-screen overflow-hidden bg-slate-950 text-white">
    <header className="relative z-10 mx-auto flex max-w-7xl items-center justify-between gap-2 px-4 py-4 sm:gap-4 sm:px-8 sm:py-5 lg:px-10">
      <Link href="/" className="flex min-w-0 items-center gap-2 sm:gap-3">
        <span className="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white shadow-lg shadow-emerald-500/20 sm:h-11 sm:w-11 sm:rounded-2xl">
          <Image src="/images/logo-uinsu.png" alt="Logo UIN Sumatera Utara" width={44} height={44} className="h-full w-full origin-top scale-[2.2] object-cover object-top" priority />
        </span>
        <span className="truncate text-lg font-bold tracking-tight sm:text-xl">SI-<span className="text-emerald-400">Praktikum</span></span>
      </Link>
      <nav aria-label="Account navigation" className="flex shrink-0 items-center gap-2 sm:gap-3">
        <LocaleSwitcher />
        <div className="hidden items-center gap-2 sm:flex">
          <Link href={destination} className="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-white/5">{t(locale, signedIn ? 'landing.dashboard' : 'landing.signIn')}</Link>
          {!signedIn && <Link href="/register" className="inline-flex rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-400">{t(locale, 'landing.signUp')}</Link>}
        </div>
        <button
          type="button"
          className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/15 text-slate-100 transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 sm:hidden"
          aria-label={isMenuOpen ? (locale === 'id' ? 'Tutup menu' : 'Close menu') : (locale === 'id' ? 'Buka menu' : 'Open menu')}
          aria-expanded={isMenuOpen}
          aria-controls="landing-mobile-menu"
          onClick={() => setIsMenuOpen((open) => !open)}
        >
          {isMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
        </button>
      </nav>
      {isMenuOpen && <div id="landing-mobile-menu" className="absolute right-4 top-full z-20 flex w-[min(18rem,calc(100vw-2rem))] flex-col gap-2 rounded-2xl border border-white/10 bg-slate-900 p-3 shadow-2xl shadow-black/40 sm:hidden">
        <Link href={destination} onClick={closeMenu} className="rounded-xl px-4 py-3 text-sm font-semibold text-slate-100 transition hover:bg-white/10">{t(locale, signedIn ? 'landing.dashboard' : 'landing.signIn')}</Link>
        {!signedIn && <Link href="/register" onClick={closeMenu} className="rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-400">{t(locale, 'landing.signUp')}</Link>}
      </div>}
    </header>
    <div className="relative mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
      <div className="pointer-events-none absolute -right-40 top-10 h-[32rem] w-[32rem] rounded-full bg-emerald-500/15 blur-3xl" /><div className="pointer-events-none absolute -left-40 top-64 h-80 w-80 rounded-full bg-emerald-400/10 blur-3xl" />
      <section className="relative grid min-h-[620px] items-center gap-12 py-12 sm:gap-14 sm:py-20 lg:grid-cols-[1.1fr_0.9fr] lg:py-24">
        <div className="min-w-0"><div className="mb-6 inline-flex max-w-full items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-2 text-xs font-semibold text-emerald-300"><CheckCircle2 className="h-4 w-4 shrink-0" /> {t(locale, 'landing.pill')}</div>
          <h1 className="max-w-3xl text-4xl font-bold leading-[1.12] tracking-tight sm:text-5xl lg:text-6xl">{t(locale, 'landing.title')}</h1>
          <p className="mt-6 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">{t(locale, 'landing.description')}</p>
          <div className="mt-9 flex flex-col gap-3 sm:flex-row"><Link href={destination} className="inline-flex min-h-14 items-center justify-center gap-2 rounded-2xl bg-emerald-500 px-7 py-4 text-sm font-bold shadow-xl shadow-emerald-500/20 transition hover:bg-emerald-400">{t(locale, signedIn ? 'landing.openDashboard' : 'landing.getStarted')} <ArrowRight className="h-4 w-4" /></Link>{!signedIn && <Link href="/register" className="inline-flex min-h-14 items-center justify-center rounded-2xl border border-white/15 px-7 py-4 text-sm font-semibold text-slate-200 transition hover:bg-white/5">{t(locale, 'landing.signUpStudents')}</Link>}</div>
          <div className="mt-10 grid max-w-2xl grid-cols-1 gap-3 sm:mt-12 sm:grid-cols-3"><div className="rounded-2xl border border-white/10 bg-white/[0.04] p-4"><UsersRound className="h-5 w-5 text-emerald-300" /><p className="mt-3 text-sm font-semibold">{t(locale, 'landing.featureClasses')}</p><p className="mt-1 text-xs leading-5 text-slate-400">{t(locale, 'landing.featureClassesText')}</p></div><div className="rounded-2xl border border-white/10 bg-white/[0.04] p-4"><ClipboardCheck className="h-5 w-5 text-emerald-300" /><p className="mt-3 text-sm font-semibold">{t(locale, 'landing.featureApprovals')}</p><p className="mt-1 text-xs leading-5 text-slate-400">{t(locale, 'landing.featureApprovalsText')}</p></div><div className="rounded-2xl border border-white/10 bg-white/[0.04] p-4"><GraduationCap className="h-5 w-5 text-emerald-300" /><p className="mt-3 text-sm font-semibold">{t(locale, 'landing.featureAttendance')}</p><p className="mt-1 text-xs leading-5 text-slate-400">{t(locale, 'landing.featureAttendanceText')}</p></div></div>
        </div>
        <div className="relative mx-auto w-full max-w-md"><div className="absolute -inset-5 rounded-[2.5rem] bg-emerald-500/10 blur-2xl" /><div className="relative rounded-[2rem] border border-white/10 bg-slate-900 p-5 shadow-2xl shadow-black/40 sm:bg-gradient-to-br sm:from-slate-800 sm:to-slate-900 sm:p-7"><div className="flex items-start justify-between gap-3"><div><p className="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-300">{t(locale, 'landing.summary')}</p><h2 className="mt-2 text-xl font-bold">{t(locale, 'landing.active')}</h2></div><span className="shrink-0 rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300">{t(locale, 'landing.unified')}</span></div><div className="mt-7 space-y-3"><div className="rounded-2xl border border-white/10 bg-slate-950/60 p-4"><div className="flex items-center gap-3"><span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300"><BookOpen className="h-5 w-5" /></span><div className="min-w-0 flex-1"><p className="text-sm font-semibold">{t(locale, 'landing.modules')}</p><p className="mt-1 text-xs text-slate-400">{t(locale, 'landing.modulesText')}</p></div><CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-400" /></div></div><div className="rounded-2xl border border-white/10 bg-slate-950/60 p-4"><div className="flex items-center gap-3"><span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300"><ClipboardCheck className="h-5 w-5" /></span><div className="min-w-0 flex-1"><p className="text-sm font-semibold">{t(locale, 'landing.attendance')}</p><p className="mt-1 text-xs text-slate-400">{t(locale, 'landing.attendanceText')}</p></div><span className="h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-400" /></div></div><div className="rounded-2xl border border-white/10 bg-slate-950/60 p-4"><div className="flex items-center gap-3"><span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300"><GraduationCap className="h-5 w-5" /></span><div className="min-w-0 flex-1"><p className="text-sm font-semibold">{t(locale, 'landing.review')}</p><p className="mt-1 text-xs text-slate-400">{t(locale, 'landing.reviewText')}</p></div><CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-400" /></div></div></div><div className="mt-6 flex items-center gap-3 border-t border-white/10 pt-5"><div className="flex -space-x-2"><span className="h-8 w-8 rounded-full border-2 border-slate-900 bg-emerald-400" /><span className="h-8 w-8 rounded-full border-2 border-slate-900 bg-emerald-600" /><span className="h-8 w-8 rounded-full border-2 border-slate-900 bg-emerald-200" /></div><p className="text-xs text-slate-400">{t(locale, 'landing.teamFlow')}</p></div></div></div>
      </section>
    </div>
  </main>;
}
