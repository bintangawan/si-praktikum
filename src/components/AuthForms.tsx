'use client';

import Link from 'next/link';
import Image from 'next/image';
import { useRouter } from 'next/navigation';
import { useState, type ButtonHTMLAttributes, type FormEvent, type ReactNode } from 'react';
import { ArrowLeft, ArrowRight, Eye, EyeOff, LoaderCircle, LockKeyhole, Mail, CalendarDays, CircleCheck, UsersRound } from 'lucide-react';
import { appName } from '@/lib/app-config';
import { showAppAlert } from '@/lib/alerts';
import { localizeServerMessage, t } from '@/lib/locale';
import { useLocale } from '@/lib/locale-context';
import { LocaleSwitcher } from '@/components/LocaleSwitcher';
import { trpc } from '@/trpc/react';

function AuthFrame({ title, subtitle, children }: { title: string; subtitle: string; children: ReactNode }) {
  const locale = useLocale();
  return <main className="min-h-screen min-h-[100dvh] bg-slate-50 text-slate-900">
    <div className="mx-auto grid min-h-screen min-h-[100dvh] max-w-[1440px] lg:grid-cols-[1.05fr_0.95fr]">
      <section className="relative hidden overflow-hidden bg-emerald-950 px-12 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-20 xl:py-16">
        <div className="pointer-events-none absolute -right-24 top-1/4 h-80 w-80 rounded-full border border-white/10" /><div className="pointer-events-none absolute -right-8 top-1/3 h-52 w-52 rounded-full border border-white/10" />
        <Link href="/" className="relative z-10 inline-flex w-fit items-center gap-4 rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur-sm transition hover:bg-white/15"><span className="flex h-14 w-14 items-center justify-center rounded-xl bg-white p-2 shadow-lg"><Image src="/images/logo-uinsu.png" alt={`Logo ${t(locale, 'auth.university')}`} width={48} height={48} className="h-full w-full object-contain" /></span><span><span className="block text-xs font-semibold uppercase tracking-[0.18em] text-emerald-200">{t(locale, 'auth.university')}</span><span className="block text-lg font-bold tracking-tight">{appName}</span></span></Link>
        <div className="relative z-10 max-w-xl py-12"><span className="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-4 py-2 text-xs font-semibold text-emerald-100"><span className="h-2 w-2 rounded-full bg-emerald-300" />{t(locale, 'auth.pill')}</span><h2 className="text-4xl font-extrabold leading-tight tracking-tight xl:text-5xl">{locale === 'id' ? <>Praktikum lebih tertata, <span className="text-emerald-300">progres lebih terpantau.</span></> : <span className="text-emerald-50">{t(locale, 'auth.heroTitle')}</span>}</h2><p className="mt-6 max-w-lg text-base leading-8 text-emerald-100/75">{t(locale, 'auth.heroDescription')}</p><div className="mt-10 grid max-w-lg grid-cols-3 gap-3"><div className="rounded-2xl border border-white/10 bg-white/[0.07] p-4"><CalendarDays className="mb-3 h-6 w-6 text-emerald-300" /><p className="text-xs font-semibold leading-5 text-emerald-50">{t(locale, 'auth.featureSchedule')}</p></div><div className="rounded-2xl border border-white/10 bg-white/[0.07] p-4"><CircleCheck className="mb-3 h-6 w-6 text-emerald-300" /><p className="text-xs font-semibold leading-5 text-emerald-50">{t(locale, 'auth.featureReview')}</p></div><div className="rounded-2xl border border-white/10 bg-white/[0.07] p-4"><UsersRound className="mb-3 h-6 w-6 text-emerald-300" /><p className="text-xs font-semibold leading-5 text-emerald-50">{t(locale, 'auth.featureAccess')}</p></div></div></div>
        <p className="relative z-10 text-xs text-emerald-200/60">© {new Date().getFullYear()} {t(locale, 'auth.university')}</p>
      </section>
      <section className="flex min-h-screen min-h-[100dvh] items-center justify-center px-4 py-8 sm:px-8 lg:px-12 xl:px-20"><div className="w-full max-w-md"><Link href="/" className="mb-5 flex items-center justify-center gap-3 lg:hidden"><span className="flex h-12 w-12 items-center justify-center rounded-xl bg-white p-2 shadow-lg ring-1 ring-slate-200"><Image src="/images/logo-uinsu.png" alt={`Logo ${t(locale, 'auth.university')}`} width={48} height={48} className="h-full w-full object-contain" /></span><span><span className="block text-[10px] font-semibold uppercase tracking-[0.16em] text-emerald-700">{t(locale, 'auth.university')}</span><span className="block text-lg font-extrabold tracking-tight">{appName}</span></span></Link><div className="mb-3 flex justify-end"><LocaleSwitcher /></div><div className="rounded-[2rem] border border-white/80 bg-white/90 p-5 shadow-2xl shadow-slate-900/[0.08] backdrop-blur-xl sm:p-9"><h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{title}</h1><p className="mt-2 text-sm leading-6 text-slate-500">{subtitle}</p><div className="mt-7">{children}</div></div><p className="mt-6 text-center text-xs leading-5 text-slate-400">{t(locale, 'auth.help')}</p></div></section>
    </div>
  </main>;
}

function Field({ label, icon, error, ...props }: React.InputHTMLAttributes<HTMLInputElement> & { label: string; icon?: ReactNode; error?: string }) {
  return <label className="block"><span className="mb-2 block text-sm font-semibold text-slate-700">{label}</span><span className="group relative block">{icon && <span className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600">{icon}</span>}<input {...props} className={`w-full rounded-xl border border-slate-200 bg-slate-50/70 py-3.5 ${icon ? 'pl-11' : 'px-4'} pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10`} /></span>{error && <span className="mt-1 block text-xs text-rose-600">{error}</span>}</label>;
}

function Submit({ children, pending, type = 'submit', ...props }: ButtonHTMLAttributes<HTMLButtonElement> & { pending?: boolean }) {
  return <button type={type} disabled={pending} {...props} className="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-700/20 transition hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-emerald-500/20 active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-70">{pending && <LoaderCircle className="h-4 w-4 animate-spin" />}{children}</button>;
}

function Message({ children, error = false }: { children?: string; error?: boolean }) {
  return children ? <p role={error ? 'alert' : 'status'} className={`rounded-xl border px-4 py-3 text-sm leading-6 ${error ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-800'}`}>{children}</p> : null;
}

export function LoginForm() {
  const router = useRouter();
  const locale = useLocale();
  const [showPassword, setShowPassword] = useState(false);
  const login = trpc.auth.login.useMutation({ onSuccess: (result) => { router.replace(result.redirectTo); router.refresh(); }, onError: (reason) => { void showAppAlert('error', t(locale, 'auth.loginErrorTitle'), localizeServerMessage(locale, reason.message), locale); } });
  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); const data = new FormData(event.currentTarget);
    login.mutate({ identifier: String(data.get('identifier')), password: String(data.get('password')), remember: data.get('remember') === 'on' });
  }
  return <AuthFrame title={t(locale, 'auth.loginTitle')} subtitle={t(locale, 'auth.loginSubtitle')}>
    <form onSubmit={submit} className="space-y-5">
      <Field name="identifier" label={t(locale, 'auth.identifier')} autoComplete="username" required icon={<Mail className="h-4 w-4" />} placeholder={t(locale, 'auth.identifierPlaceholder')} />
      <label className="block"><span className="mb-2 block text-sm font-semibold text-slate-700">{t(locale, 'auth.password')}</span><span className="group relative block"><LockKeyhole className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600" /><input name="password" type={showPassword ? 'text' : 'password'} autoComplete="current-password" required className="w-full rounded-xl border border-slate-200 bg-slate-50/70 py-3.5 pl-11 pr-12 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10" placeholder={t(locale, 'auth.passwordPlaceholder')} /><button type="button" onClick={() => setShowPassword((value) => !value)} className="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-2 text-slate-400 hover:bg-slate-100" aria-label={t(locale, showPassword ? 'auth.hidePassword' : 'auth.showPassword')}>{showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}</button></span></label>
      <div className="flex flex-wrap items-center justify-between gap-3"><label className="inline-flex cursor-pointer items-center"><input type="checkbox" name="remember" className="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" /><span className="ms-2 text-xs font-medium text-slate-600">{t(locale, 'auth.remember')}</span></label><Link href="/forgot-password" className="text-xs font-semibold text-emerald-700 hover:text-emerald-800">{t(locale, 'auth.forgotPassword')}</Link></div>
      <Submit pending={login.isPending}>{t(locale, 'auth.loginSubmit')} <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" /></Submit>
    </form><p className="mt-6 text-center text-sm text-slate-500">{t(locale, 'auth.noAccount')} <Link href="/register" className="font-semibold text-emerald-700">{t(locale, 'auth.registerLink')}</Link></p>
  </AuthFrame>;
}

export function RegisterForm() {
  const router = useRouter();
  const locale = useLocale();
  const register = trpc.auth.register.useMutation({
    onSuccess: async () => {
      router.replace('/login');
      await showAppAlert('success', t(locale, 'auth.registerSuccessTitle'), t(locale, 'auth.registerSuccessText'), locale);
    },
    onError: (reason) => { void showAppAlert('error', t(locale, 'auth.registerErrorTitle'), localizeServerMessage(locale, reason.message), locale); },
  });
  function submit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); const data = new FormData(event.currentTarget); register.mutate({ id: String(data.get('id')), name: String(data.get('name')), email: String(data.get('email')), password: String(data.get('password')), passwordConfirmation: String(data.get('passwordConfirmation')) }); }
  return <AuthFrame title={t(locale, 'auth.registerTitle')} subtitle={t(locale, 'auth.registerSubtitle')}>
    <form onSubmit={submit} className="space-y-4">
      <Field name="id" label={t(locale, 'auth.nim')} autoComplete="username" required maxLength={20} placeholder="0701222090" />
      <Field name="name" label={t(locale, 'auth.name')} autoComplete="name" required maxLength={255} placeholder={t(locale, 'auth.namePlaceholder')} />
      <Field name="email" label={t(locale, 'auth.email')} type="email" autoComplete="email" required placeholder={t(locale, 'auth.emailPlaceholder')} />
      <Field name="password" label={t(locale, 'auth.password')} type="password" autoComplete="new-password" required minLength={8} placeholder={t(locale, 'auth.passwordMin')} />
      <Field name="passwordConfirmation" label={t(locale, 'auth.passwordConfirmation')} type="password" autoComplete="new-password" required minLength={8} placeholder={t(locale, 'auth.repeatPassword')} />
      <Submit pending={register.isPending}>{t(locale, 'auth.registerSubmit')} <ArrowRight className="h-4 w-4" /></Submit>
    </form><p className="mt-6 text-center text-sm text-slate-500">{t(locale, 'auth.hasAccount')} <Link href="/login" className="font-semibold text-emerald-700">{t(locale, 'auth.signIn')}</Link></p>
  </AuthFrame>;
}

export function PasswordHelpForm() {
  const locale = useLocale();
  return <AuthFrame title={t(locale, 'auth.forgotTitle')} subtitle={t(locale, 'auth.forgotSubtitle')}>
    <div className="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900"><p className="font-semibold">{t(locale, 'auth.forgotContactTitle')}</p><p className="mt-2">{t(locale, 'auth.forgotContactText')}</p></div>
    <Link href="/login" className="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700"><ArrowLeft className="h-4 w-4" /> {t(locale, 'auth.backToLogin')}</Link>
  </AuthFrame>;
}

export function FirstLoginPasswordForm() {
  const router = useRouter(); const locale = useLocale(); const [error, setError] = useState('');
  const update = trpc.auth.updatePassword.useMutation({ onSuccess: () => { router.replace('/dashboard'); router.refresh(); }, onError: (reason) => setError(localizeServerMessage(locale, reason.message)) });
  function submit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); setError(''); const data = new FormData(event.currentTarget); update.mutate({ currentPassword: String(data.get('currentPassword')), password: String(data.get('password')), passwordConfirmation: String(data.get('passwordConfirmation')) }); }
  return <AuthFrame title={t(locale, 'auth.changePasswordTitle')} subtitle={t(locale, 'auth.changePasswordSubtitle')}>
    <form onSubmit={submit} className="space-y-5"><Message error>{error}</Message><Field name="currentPassword" type="password" label={t(locale, 'auth.currentPassword')} autoComplete="current-password" required /><Field name="password" type="password" label={t(locale, 'auth.newPassword')} autoComplete="new-password" minLength={8} required /><Field name="passwordConfirmation" type="password" label={t(locale, 'auth.newPasswordConfirmation')} autoComplete="new-password" minLength={8} required /><Submit pending={update.isPending}>{t(locale, 'auth.savePassword')} <ArrowRight className="h-4 w-4" /></Submit></form>
  </AuthFrame>;
}
