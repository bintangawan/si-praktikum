'use client';

import Link from 'next/link';
import Image from 'next/image';
import { usePathname, useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { Archive, BookOpen, CalendarDays, ChevronLeft, ChevronRight, ClipboardCheck, GraduationCap, LayoutDashboard, LogOut, Menu, UserRound, Users, X } from 'lucide-react';
import { trpc } from '@/trpc/react';
import type { SessionUser } from '@/server/auth/session';
import { appName } from '@/lib/app-config';
import { t } from '@/lib/locale';
import { useLocale } from '@/lib/locale-context';
import { confirmAppAction } from '@/lib/alerts';
import { LocaleSwitcher } from '@/components/LocaleSwitcher';

type NavigationKey = 'nav.dashboard' | 'nav.courses' | 'nav.archives' | 'nav.tutorials' | 'nav.mySubmissions' | 'nav.reviews' | 'nav.approvals' | 'nav.users' | 'nav.importUsers' | 'nav.semesters' | 'nav.profile';
type NavigationItem = { label: NavigationKey; href: string; icon: typeof LayoutDashboard; roles?: SessionUser['role'][] };

const navigation: NavigationItem[] = [
  { label: 'nav.dashboard', href: '/dashboard', icon: LayoutDashboard },
  { label: 'nav.courses', href: '/courses', icon: BookOpen },
  { label: 'nav.archives', href: '/arsip', icon: Archive },
  { label: 'nav.tutorials', href: '/tutorials', icon: GraduationCap },
  { label: 'nav.mySubmissions', href: '/my-submissions', icon: ClipboardCheck, roles: ['Mahasiswa'] },
  { label: 'nav.reviews', href: '/submissions/pending', icon: ClipboardCheck, roles: ['Dosen', 'Aslab', 'Laboran'] },
  { label: 'nav.approvals', href: '/account-approvals', icon: Users, roles: ['Aslab', 'Laboran'] },
  { label: 'nav.users', href: '/users-management', icon: Users, roles: ['Laboran'] },
  { label: 'nav.importUsers', href: '/import-users', icon: Users, roles: ['Laboran'] },
  { label: 'nav.semesters', href: '/semesters', icon: CalendarDays, roles: ['Laboran'] },
  { label: 'nav.profile', href: '/profile', icon: UserRound },
];

export function AppShell({ user, children }: { user: SessionUser; children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const locale = useLocale();
  const logout = trpc.auth.logout.useMutation({ onSuccess: () => { router.replace('/login'); router.refresh(); } });
  const [open, setOpen] = useState(false);
  const [expanded, setExpanded] = useState(true);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setExpanded(window.innerWidth >= 1024 && window.localStorage.getItem('sidebarState') !== 'false');
  }, []);

  function toggleSidebar() {
    setExpanded((current) => {
      const next = !current;
      if (window.innerWidth >= 1024) window.localStorage.setItem('sidebarState', String(next));
      return next;
    });
  }

  function requestLogout() {
    const title = locale === 'en' ? 'Are you sure you want to sign out?' : 'Yakin ingin keluar?';
    const description = locale === 'en' ? 'You will be signed out of this account.' : 'Anda akan keluar dari akun ini.';
    void confirmAppAction(title, description, 'warning', locale).then((confirmed) => {
      if (confirmed) logout.mutate();
    });
  }

  const items = navigation.filter((item) => !item.roles || item.roles.includes(user.role));
  return (
    <div className="flex h-[100dvh] flex-col overflow-hidden bg-[#f6f8f7]" onKeyDown={(event) => { if (event.key === 'Escape') setOpen(false); }}>
      <header className="z-30 flex shrink-0 items-center justify-between border-b border-gray-200 bg-white px-3 py-3 shadow-sm sm:px-6">
        <div className="flex min-w-0 items-center gap-2 sm:gap-6">
          <button aria-label={t(locale, 'nav.toggleMenu')} onClick={() => window.innerWidth < 1024 ? setOpen(true) : toggleSidebar()} className="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 active:scale-95">
            <Menu className="h-6 w-6" />
          </button>
          <Link href="/dashboard" className="truncate text-base font-semibold tracking-tight text-emerald-900 sm:text-xl">{appName}</Link>
        </div>
        <div className="flex shrink-0 items-center gap-1 sm:gap-3">
          <LocaleSwitcher />
          <div className="hidden min-w-0 text-right sm:block">
            <p className="max-w-52 truncate text-sm font-bold text-gray-800">{user.name}</p>
            <p className="text-xs font-medium text-gray-500">{t(locale, `role.${user.role}`)}</p>
          </div>
          <div className="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-emerald-100 font-bold text-emerald-800 shadow-sm ring-1 ring-gray-200 sm:h-10 sm:w-10">
            {user.avatar ? <Image src="/api/avatar" alt={user.name} width={40} height={40} unoptimized className="h-full w-full object-cover" /> : user.name.split(/\s+/).slice(0, 2).map((word) => word[0]).join('').toUpperCase()}
          </div>
          <button title={t(locale, 'nav.logout')} onClick={requestLogout} disabled={logout.isPending} className="rounded-lg p-1.5 text-slate-500 transition hover:bg-rose-50 hover:text-rose-700 sm:p-2" aria-label={t(locale, 'nav.logout')}><LogOut className="h-5 w-5" /></button>
        </div>
      </header>

      {open && <button aria-label={t(locale, 'nav.closeMenu')} onClick={() => setOpen(false)} className="fixed inset-0 z-40 bg-slate-950/40 lg:hidden" />}
      <div className="flex min-h-0 flex-1 overflow-hidden">
        <aside className={`${expanded ? 'lg:w-72' : 'lg:w-20'} fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-all duration-200 lg:static lg:translate-x-0 ${open ? 'translate-x-0' : ''}`}>
          <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4 lg:hidden">
            <span className="font-semibold text-emerald-900">{t(locale, 'nav.mobile')}</span><button onClick={() => setOpen(false)} className="rounded-lg p-2 hover:bg-slate-100" aria-label={t(locale, 'nav.closeMenu')}><X className="h-5 w-5" /></button>
          </div>
          <div className="flex items-center justify-between px-5 pb-3 pt-5">
            {expanded && <p className="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">{t(locale, 'nav.menu')}</p>}
            <button className="hidden rounded-md p-1 text-slate-400 hover:bg-slate-100 lg:block" onClick={toggleSidebar} aria-label={t(locale, 'nav.sidebarToggle')}>{expanded ? <ChevronLeft className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}</button>
          </div>
          <nav className="custom-scrollbar flex-1 space-y-1 overflow-y-auto px-3 pb-5">
            {items.map(({ label, href, icon: Icon }) => {
              const active = pathname === href || (href !== '/dashboard' && pathname.startsWith(`${href}/`));
              const labelText = t(locale, label);
              return <Link key={href} href={href} title={!expanded ? labelText : undefined} onClick={() => setOpen(false)} className={`flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition ${active ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100' : 'text-slate-600 hover:bg-slate-50 hover:text-emerald-800'} ${!expanded ? 'lg:justify-center lg:px-0' : ''}`}>
                <Icon className="h-5 w-5 shrink-0" /><span className={!expanded ? 'lg:hidden' : ''}>{labelText}</span>
              </Link>;
            })}
          </nav>
          {expanded && <div className="border-t border-slate-100 p-4"><div className="rounded-2xl bg-emerald-950 p-4 text-white"><p className="text-xs font-semibold text-emerald-200">{appName}</p><p className="mt-1 text-xs leading-5 text-emerald-100/85">{t(locale, 'nav.shellSummary')}</p></div></div>}
        </aside>

        <main className="custom-scrollbar min-w-0 flex-1 overflow-y-auto">
          <div className="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">{children}</div>
        </main>
      </div>
    </div>
  );
}
