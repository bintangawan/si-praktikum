'use client';

import Link from 'next/link';
import Image from 'next/image';
import { useRouter, useSearchParams } from 'next/navigation';
import { Fragment, useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import { ArrowDownToLine, ArrowLeft, ArrowRight, BookOpen, CalendarDays, Check, Clock3, Download, FileText, GraduationCap, LockKeyhole, LockKeyholeOpen, Plus, Printer, RotateCcw, Search, Trash2, Users } from 'lucide-react';
import type { SessionUser } from '@/server/auth/session';
import { trpc } from '@/trpc/react';
import { appName } from '@/lib/app-config';
import { confirmAppAction, promptAppText, showAppAlert } from '@/lib/alerts';
import { localizeServerMessage, t } from '@/lib/locale';
import { useLocale } from '@/lib/locale-context';
import type { GradeExportCourse, GradeExportRow } from '@/lib/grade-export';
import { LaprakReport, type LaprakReportData } from './LaprakReport';
import { canCreateCourse, canViewEnrollmentCode } from '@/lib/course-permissions';

// Server DTOs are intentionally treated as dynamic records at this shared view boundary.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
type AnyRecord = Record<string, any>;

const card = 'rounded-2xl border border-slate-200 bg-white shadow-sm';
const primary = 'inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60';
const secondary = 'inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800 disabled:opacity-60';
const inputClass = 'w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';

function Heading({ title, description, actions, eyebrow }: { title: string; description?: string; actions?: ReactNode; eyebrow?: string }) {
  return <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.15em] text-emerald-800">{eyebrow ?? appName}</p><h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{title}</h1>{description && <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{description}</p>}</div>{actions}</div>;
}

function Notice({ children, error = false }: { children?: string; error?: boolean }) {
  return children ? <p role="status" className={`rounded-xl border px-4 py-3 text-sm ${error ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-800'}`}>{children}</p> : null;
}

function copy(locale: ReturnType<typeof useLocale>, key: Parameters<typeof t>[1], values: Record<string, string | number> = {}) {
  return Object.entries(values).reduce((value, [name, replacement]) => value.replaceAll(`{${name}}`, String(replacement)), t(locale, key));
}

function localizedStatus(locale: ReturnType<typeof useLocale>, value: string | null | undefined): string {
  if (!value) return '';
  if (locale !== 'en') return value;
  if (value.includes('/')) return value.split('/').map((part) => localizedStatus(locale, part.trim())).join(' / ');
  const statuses: Record<string, string> = {
    ACC: 'Approved',
    Pending: 'Pending',
    Revisi: 'Revision requested',
    Ditolak: 'Rejected',
    Diterima: 'Accepted',
    'Belum mengumpulkan': 'Not submitted',
    'Menunggu pemeriksaan': 'Awaiting review',
    Hadir: 'Present',
    Sakit: 'Sick',
    Izin: 'Excused',
    'Tanpa Keterangan': 'Unexcused',
    'Belum Presensi': 'Not marked',
  };
  return statuses[value] ?? value;
}

function localizedSemesterName(locale: ReturnType<typeof useLocale>, value: string | null | undefined) {
  if (!value || locale !== 'en') return value ?? '';
  return value.replace(/\bGanjil\b/gi, 'Odd').replace(/\bGenap\b/gi, 'Even');
}

function localizedModuleTitle(locale: ReturnType<typeof useLocale>, value: string | null | undefined, number: number) {
  const title = value || `Modul ${number}`;
  if (locale === 'en' && title === `Modul ${number}`) return `Module ${number}`;
  return title;
}

function useAction<TData = unknown>(mutation: { isPending: boolean; mutate: (input: TData, options?: { onSuccess?: () => void; onError?: (error: { message: string }) => void }) => void }) {
  const locale = useLocale();
  const router = useRouter(); const [message, setMessage] = useState(''); const [error, setError] = useState('');
  const run = (input: TData, success = 'Perubahan berhasil disimpan.') => mutation.mutate(input, {
    onSuccess: () => { setMessage(success); setError(''); router.refresh(); },
    onError: (reason) => { setError(localizeServerMessage(locale, reason.message)); setMessage(''); },
  });
  return { run, message, error, pending: mutation.isPending, setMessage, setError };
}

function DashboardView({ data, user }: { data: AnyRecord; user: SessionUser }) {
  const locale = useLocale();
  if (user.role === 'Dosen') return <LecturerDashboardView data={data} user={user} />;
  if (user.role === 'Aslab' || user.role === 'Laboran') return <StaffDashboardView data={data} user={user} />;
  const stats = [{ label: t(locale, 'ui.activeCourses'), value: data.courseCount, icon: BookOpen }, { label: t(locale, 'module.title'), value: data.moduleCount, icon: CalendarDays },
    { label: t(locale, 'nav.mySubmissions'), value: data.pendingCount, icon: Clock3 }];
  const roleDescription = user.role === 'Mahasiswa' ? t(locale, 'ui.studentDashboard') : user.role === 'Aslab' ? t(locale, 'ui.assistantDashboard') : t(locale, 'ui.adminDashboard');
  return <div className="space-y-6 sm:space-y-8">
    <section className="relative overflow-hidden rounded-3xl bg-emerald-950 p-6 text-white shadow-xl shadow-emerald-950/10 sm:p-8 lg:p-10"><div className="pointer-events-none absolute -right-10 -top-16 h-56 w-56 rounded-full bg-emerald-400/15 blur-3xl" /><div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"><div><div className="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-emerald-100"><BookOpen className="h-4 w-4" /> {t(locale, 'role.' + user.role as Parameters<typeof t>[1])}</div><h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{t(locale, 'ui.dashboardGreeting')}, {t(locale, 'role.' + user.role as Parameters<typeof t>[1])}!</h1><p className="mt-1 text-sm font-medium text-emerald-100">{user.name}</p><p className="mt-3 max-w-2xl text-sm leading-6 text-emerald-100/80">{roleDescription}</p></div><div className="hidden h-20 w-20 items-center justify-center rounded-3xl border border-white/10 bg-white/10 text-emerald-100 sm:flex"><GraduationCap className="h-10 w-10" /></div></div></section>
    {data.activeSemester && <div className="flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><CalendarDays className="h-5 w-5" /><span>{t(locale, 'ui.activeSemester')}: <strong>{localizedSemesterName(locale, data.activeSemester.name)}</strong></span></div>}
    <section className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">{stats.map(({ label, value, icon: Icon }) => <article key={label} className={`${card} flex items-center gap-4 p-4 sm:p-5`}><span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><Icon className="h-5 w-5" /></span><div><p className="text-xs font-medium text-slate-500">{label}</p><p className="mt-1 text-2xl font-bold text-slate-900">{value ?? 0}</p></div></article>)}</section>
    <section><div className="mb-4 flex items-center justify-between"><h2 className="text-lg font-bold text-slate-900">{t(locale, 'ui.courseList')}</h2><Link href="/courses" className="text-sm font-semibold text-emerald-700 hover:text-emerald-900">{t(locale, 'ui.seeAll')} <ArrowRight className="inline h-4 w-4" /></Link></div>{data.courses?.length ? <div className="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">{data.courses.map((course: AnyRecord) => <Link key={course.id} href={`/courses/${course.slug}`} className={`${card} group block p-5 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg sm:p-6`}><div className="flex items-start justify-between"><span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-700 group-hover:text-white"><BookOpen className="h-6 w-6" /></span><span className="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{t(locale, 'ui.active')}</span></div><p className="mt-5 text-xs font-semibold text-slate-500">{localizedSemesterName(locale, course.semesterName)}</p><h3 className="mt-1 text-lg font-bold text-slate-900">{course.name}</h3><p className="mt-1 text-sm text-slate-500">{t(locale, 'ui.class')} {course.group}</p><div className="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4"><div className="rounded-xl bg-slate-50 p-3"><p className="text-xs text-slate-500">{t(locale, 'ui.students')}</p><p className="mt-1 font-bold text-slate-800">{course.studentCount}</p></div><div className="rounded-xl bg-slate-50 p-3"><p className="text-xs text-slate-500">{t(locale, 'ui.module')}</p><p className="mt-1 font-bold text-slate-800">{course.moduleCount}</p></div></div></Link>)}</div> : <Empty message={t(locale, 'ui.emptyActiveCourses')} />}</section>
  </div>;
}

function LecturerDashboardView({ data, user }: { data: AnyRecord; user: SessionUser }) {
  const locale = useLocale();
  const stats = [
    { label: t(locale, 'ui.totalClasses'), value: data.courseCount ?? 0, icon: BookOpen },
    { label: t(locale, 'ui.activeCourses'), value: data.activeCourseCount ?? 0, icon: CalendarDays },
    { label: t(locale, 'ui.totalModules'), value: data.moduleCount ?? 0, icon: FileText },
  ];
  return <div className="space-y-6 sm:space-y-8">
    <section className="relative overflow-hidden rounded-3xl bg-emerald-950 p-6 text-white shadow-xl shadow-emerald-950/10 sm:p-8 lg:p-10">
      <div className="pointer-events-none absolute -right-10 -top-16 h-56 w-56 rounded-full bg-emerald-400/15 blur-3xl" />
      <div className="pointer-events-none absolute -bottom-24 left-1/3 h-48 w-48 rounded-full bg-emerald-300/10 blur-3xl" />
      <div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div className="min-w-0">
          <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-emerald-100"><BookOpen className="h-4 w-4" />{t(locale, 'ui.lecturerPanel')}</div>
          <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{t(locale, 'ui.dashboardGreeting')}, {t(locale, 'role.Dosen')}!</h1>
          <p className="mt-1 break-words text-sm font-medium text-emerald-100 sm:text-base">{user.name}</p>
          <p className="mt-3 max-w-2xl text-sm leading-6 text-emerald-100/80 sm:text-base sm:leading-7">{t(locale, 'ui.lecturerDashboard')}</p>
        </div>
        <div className="hidden h-20 w-20 shrink-0 items-center justify-center rounded-3xl border border-white/10 bg-white/10 text-emerald-100 sm:flex"><GraduationCap className="h-10 w-10" /></div>
      </div>
    </section>
    <section className="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4" aria-label={t(locale, 'ui.lecturerSummary')}>
      {stats.map(({ label, value, icon: Icon }) => <article key={label} className={`${card} flex items-center gap-4 p-4 shadow-sm sm:p-5`}>
        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><Icon className="h-5 w-5" /></span>
        <div><p className="text-xs font-semibold text-slate-500">{label}</p><p className="mt-0.5 text-2xl font-bold tracking-tight text-slate-900">{value}</p></div>
      </article>)}
    </section>
    <section>
      <div className="mb-4 sm:mb-5"><h2 className="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">{t(locale, 'ui.assignedClasses')}</h2><p className="mt-1 text-sm leading-6 text-slate-500">{t(locale, 'ui.assignedClassesDescription')}</p></div>
      <AssignedCourseCards courses={data.courses ?? []} />
    </section>
  </div>;
}

function StaffDashboardView({ data, user }: { data: AnyRecord; user: SessionUser }) {
  const locale = useLocale();
  const isAslab = user.role === 'Aslab';
  const roleDescription = isAslab ? t(locale, 'ui.assistantDashboard') : t(locale, 'ui.adminDashboard');
  const stats = [
    { label: t(locale, 'ui.activeCourses'), value: data.courseCount ?? 0, icon: BookOpen },
    { label: t(locale, 'ui.totalModules'), value: data.moduleCount ?? 0, icon: FileText },
    { label: t(locale, 'review.awaiting'), value: data.pendingCount ?? 0, icon: Clock3 },
    { label: t(locale, 'auth.pendingTitle'), value: data.pendingAccounts ?? 0, icon: Users },
  ];
  return <div className="space-y-6 sm:space-y-8">
    <section className="relative overflow-hidden rounded-3xl bg-emerald-950 p-6 text-white shadow-xl shadow-emerald-950/10 sm:p-8 lg:p-10">
      <div className="pointer-events-none absolute -right-10 -top-16 h-56 w-56 rounded-full bg-emerald-400/15 blur-3xl" />
      <div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div className="min-w-0"><div className="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-emerald-100"><BookOpen className="h-4 w-4" />{t(locale, `role.${user.role}` as Parameters<typeof t>[1])}</div><h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{t(locale, 'ui.dashboardGreeting')}, {t(locale, `role.${user.role}` as Parameters<typeof t>[1])}!</h1><p className="mt-1 break-words text-sm font-medium text-emerald-100 sm:text-base">{user.name}</p><p className="mt-3 max-w-2xl text-sm leading-6 text-emerald-100/80">{roleDescription}</p></div>
        <div className="hidden h-20 w-20 shrink-0 items-center justify-center rounded-3xl border border-white/10 bg-white/10 text-emerald-100 sm:flex"><GraduationCap className="h-10 w-10" /></div>
      </div>
    </section>
    {data.activeSemester && <div className="flex items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"><CalendarDays className="h-5 w-5" /><span>{t(locale, 'ui.activeSemester')}: <strong>{localizedSemesterName(locale, data.activeSemester.name)}</strong></span></div>}
    <section className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label={t(locale, 'ui.lecturerSummary')}>
      {stats.map(({ label, value, icon: Icon }) => <article key={label} className={`${card} flex items-center gap-4 p-4 shadow-sm sm:p-5`}><span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><Icon className="h-5 w-5" /></span><div><p className="text-xs font-semibold text-slate-500">{label}</p><p className="mt-0.5 text-2xl font-bold tracking-tight text-slate-900">{value}</p></div></article>)}
    </section>
    <section>
      <div className="mb-4 sm:mb-5"><h2 className="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">{t(locale, 'ui.assignedClasses')}</h2><p className="mt-1 text-sm leading-6 text-slate-500">{t(locale, 'ui.assignedClassesDescription')}</p></div>
      <AssignedCourseCards
        courses={data.courses ?? []}
        showEnrollmentCode={canViewEnrollmentCode(user.role)}
        emptyDescription={t(locale, 'ui.createClassToStart')}
        emptyAction={<Link href="/courses/create" className={`${primary} mt-4`}><Plus className="h-4 w-4" /> {t(locale, 'ui.createCourse')}</Link>}
      />
    </section>
  </div>;
}

function AssignedCourseCards({ courses, showEnrollmentCode = false, emptyDescription, emptyAction }: { courses: AnyRecord[]; showEnrollmentCode?: boolean; emptyDescription?: string; emptyAction?: ReactNode }) {
  const locale = useLocale();
  return courses.length ? <div className="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
    {courses.map((course) => {
      const isActive = !course.isArchived;
      return <article key={course.id} className={`${card} group flex min-w-0 flex-col overflow-hidden transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg`}>
        <div className="flex flex-1 flex-col p-5 sm:p-6">
          <div className="flex items-start justify-between gap-3"><span className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-700 group-hover:text-white"><BookOpen className="h-6 w-6" /></span><span className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ${isActive ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{t(locale, isActive ? 'ui.active' : 'ui.archived')}</span></div>
          <div className="mt-5 min-w-0 flex-1"><p className="text-xs font-semibold text-slate-500">{localizedSemesterName(locale, course.semesterName)}</p><h3 className="mt-1 break-words text-lg font-bold leading-snug text-slate-900 sm:text-xl">{course.name}</h3><p className="mt-1 text-sm text-slate-500">{t(locale, 'ui.class')} {course.group}</p></div>
          <div className="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4"><div className="rounded-xl bg-slate-50 p-3"><p className="text-xs font-medium text-slate-500">{t(locale, 'ui.enrolledStudents')}</p><p className="mt-1 text-sm font-bold text-slate-800">{course.studentCount}</p></div><div className="rounded-xl bg-slate-50 p-3"><p className="text-xs font-medium text-slate-500">{t(locale, 'ui.module')}</p><p className="mt-1 text-sm font-bold text-slate-800">{course.moduleCount}</p></div></div>
          <div className="mt-4 grid gap-2 text-xs text-slate-500"><p><span className="font-semibold text-slate-600">{t(locale, 'ui.lecturer')}:</span> {course.dosenName ?? '—'}</p><p><span className="font-semibold text-slate-600">{t(locale, 'ui.labAssistant')}:</span> {course.aslabName ?? '—'}</p><p><span className="font-semibold text-slate-600">{t(locale, 'ui.labAdministrator')}:</span> {course.laboranName ?? '—'}</p></div>
          {showEnrollmentCode && course.enrollmentCode && <div className="mt-4 flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2.5"><span className="text-xs font-medium text-slate-600">{t(locale, 'ui.classCode')}</span><code className="break-all font-mono text-sm font-bold tracking-wider text-emerald-900">{course.enrollmentCode}</code></div>}
        </div>
        <div className={`grid grid-cols-1 gap-2 border-t border-slate-100 bg-slate-50/70 p-4 ${isActive ? 'sm:grid-cols-3' : ''}`}>
          <Link href={`/courses/${course.slug}`} className={`${primary} text-xs sm:col-span-3`}>{t(locale, 'ui.openCourse')}<ArrowRight className="h-4 w-4" /></Link>
          {isActive && <><Link href={`/courses/${course.slug}/grades`} className={`${secondary} text-xs`}>{t(locale, 'ui.grading')}</Link><Link href={`/courses/${course.slug}/students`} className={`${secondary} text-xs`}>{t(locale, 'ui.studentList')}</Link><Link href={`/courses/${course.slug}/attendance-report`} className={`${secondary} text-xs`}>{t(locale, 'ui.laprakSummary')}</Link></>}
        </div>
      </article>;
    })}
  </div> : <div className={`${card} border-dashed px-5 py-12 text-center sm:py-16`}><BookOpen className="mx-auto h-9 w-9 text-slate-400" /><h3 className="mt-4 text-base font-bold text-slate-800">{t(locale, 'ui.noAssignedClasses')}</h3><p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">{emptyDescription ?? t(locale, 'ui.contactLabForAssignment')}</p>{emptyAction}</div>;
}

function Empty({ message }: { message: string }) { return <div className={`${card} px-5 py-12 text-center`}><BookOpen className="mx-auto h-9 w-9 text-slate-300" /><p className="mt-3 text-sm text-slate-500">{message}</p></div>; }

function CourseList({ data, user, archived = false }: { data: AnyRecord; user: SessionUser; archived?: boolean }) {
  const locale = useLocale();
  const enroll = trpc.courses.enroll.useMutation();
  const action = useAction(enroll);
  const [code, setCode] = useState('');
  const items = archived ? data : data.courses;

  return <>
    <Heading
      title={t(locale, archived ? 'ui.archives' : 'ui.courseList')}
      description={archived ? t(locale, 'ui.archiveDescription') : data.activeSemester ? copy(locale, 'ui.courseListDescription', { semester: localizedSemesterName(locale, data.activeSemester.name) }) : t(locale, 'ui.noActiveSemester')}
      actions={canCreateCourse(user.role) && !archived ? <Link href="/courses/create" className={primary}><Plus className="h-4 w-4" /> {t(locale, 'ui.createCourse')}</Link> : undefined}
    />
    {user.role === 'Mahasiswa' && !archived && <form onSubmit={(event) => { event.preventDefault(); action.run({ enrollmentCode: code }, t(locale, 'ui.joinedCourse')); }} className={card + ' mb-5 flex flex-col gap-3 p-4 sm:flex-row'}><input value={code} onChange={(event) => setCode(event.target.value.toUpperCase())} className={inputClass} placeholder={t(locale, 'ui.enrollmentCode')} maxLength={20} required /><button className={primary} disabled={action.pending}><Plus className="h-4 w-4" /> {t(locale, 'ui.joinCourse')}</button></form>}
    <Notice error={Boolean(action.error)}>{action.error}</Notice>
    <Notice>{action.message}</Notice>
    {user.role === 'Laboran' && !archived && <div className="mb-4 flex justify-end"><Link href="/courses?view=all" className={secondary}>{t(locale, 'ui.allCourses')}</Link></div>}
    {items?.length ? <div className="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
      {items.map((course: AnyRecord) => {
        const isArchived = archived || course.isArchived || course.semesterActive === false;
        return <article key={course.id} className={card + ' group flex min-w-0 flex-col p-5 transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg sm:p-6'}>
          <div className="flex items-start justify-between gap-3">
            <Link href={'/courses/' + course.slug} className="min-w-0">
              <span className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"><BookOpen className="h-6 w-6" /></span>
              <p className="mt-4 text-xs font-semibold text-slate-500">{localizedSemesterName(locale, course.semesterName)}</p>
              <h2 className="mt-1 break-words text-lg font-bold text-slate-900">{course.name}</h2>
              <p className="mt-1 text-sm text-slate-500">{t(locale, 'ui.class')} {course.group}</p>
            </Link>
            <span className={'shrink-0 rounded-full px-3 py-1 text-xs font-semibold ' + (isArchived ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700')}>{isArchived ? t(locale, 'ui.archived') : t(locale, 'ui.active')}</span>
          </div>
          {canViewEnrollmentCode(user.role) && !archived && course.enrollmentCode && <div className="mt-4 flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2.5"><span className="text-xs font-medium text-slate-600">{t(locale, 'ui.classCode')}</span><code className="break-all font-mono text-sm font-bold tracking-wider text-emerald-900">{course.enrollmentCode}</code></div>}
          <div className="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
            <div className="min-w-0"><p className="text-xs text-slate-500">{t(locale, 'ui.lecturer')}</p><p className="mt-1 break-words text-sm font-semibold text-slate-800">{course.dosenName ?? '—'}</p></div>
            <div className="min-w-0"><p className="text-xs text-slate-500">{t(locale, 'ui.labAssistant')}</p><p className="mt-1 break-words text-sm font-semibold text-slate-800">{course.aslabName ?? '—'}</p></div>
          </div>
          <div className="mt-5 flex flex-wrap items-center gap-2"><Link href={'/courses/' + course.slug} className={secondary}>{t(locale, 'ui.openCourse')} <ArrowRight className="h-4 w-4" /></Link>{user.role === 'Laboran' && !archived && <ArchiveAction slug={course.slug} archived={Boolean(course.isArchived)} />}</div>
        </article>;
      })}
    </div> : <Empty message={t(locale, archived ? 'ui.emptyArchives' : 'ui.emptyCourses')} />}
  </>;
}
function ArchiveAction({ slug, archived }: { slug: string; archived: boolean }) {
  const locale = useLocale();
  const mutation = trpc.courses.archive.useMutation();
  const router = useRouter();
  return <button onClick={() => { void confirmAppAction(t(locale, archived ? 'ui.restoreConfirm' : 'ui.archiveConfirm'), t(locale, archived ? 'ui.restoreConfirmText' : 'ui.archiveConfirmText'), 'question', locale).then((confirmed) => { if (confirmed) mutation.mutate({ slug, archived: !archived }, { onSuccess: () => router.refresh() }); }); }} className={secondary} disabled={mutation.isPending}>{t(locale, archived ? 'ui.restore' : 'ui.archive')}</button>;
}

function CourseCreate({ options, editing, staffMode }: { options: AnyRecord; editing?: AnyRecord; staffMode?: boolean }) {
  const locale = useLocale();
  const router = useRouter(); const mutation = trpc.courses.create.useMutation(); const update = trpc.courses.update.useMutation(); const staff = trpc.courses.updateStaff.useMutation();
  const [error, setError] = useState('');
  const course = editing?.course;
  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setError(''); const form = new FormData(event.currentTarget);
    const dosenId = String(form.get('dosenId')); const aslabId = String(form.get('aslabId'));
    if (staffMode) { staff.mutate({ slug: course.slug, dosenId, aslabId }, { onSuccess: () => router.push(`/courses/${course.slug}`), onError: (reason) => setError(localizeServerMessage(locale, reason.message)) }); return; }
    const values = { courseName: String(form.get('courseName')), classGroup: String(form.get('classGroup')), targetSemester: Number(form.get('targetSemester')), dosenId, aslabId };
    const onSuccess = (result: AnyRecord) => router.push(`/courses/${result.slug ?? course?.slug}`);
    const onError = (reason: { message: string }) => setError(localizeServerMessage(locale, reason.message));
    if (course) update.mutate({ ...values, laboranId: String(form.get('laboranId')), id: course.id }, { onSuccess: () => router.push(`/courses/${course.slug}`), onError });
    else mutation.mutate({ ...values, laboranId: String(form.get('laboranId')), moduleCount: Number(form.get('moduleCount')) }, { onSuccess, onError });
  }
  const pending = mutation.isPending || update.isPending || staff.isPending;
  const select = (name: string, label: string, choices: AnyRecord[], value?: string | null, disabled = false) => <label className="block"><span className="mb-2 block text-xs font-semibold text-slate-700">{label}</span><select name={disabled ? undefined : name} className={inputClass} required disabled={disabled} defaultValue={value ?? ''}><option value="" disabled>{t(locale, 'ui.select')} {label.toLowerCase()}</option>{choices.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select>{disabled && value && <input type="hidden" name={name} value={value} />}</label>;
  const title = staffMode ? t(locale, 'ui.assignStaffTitle') : course ? t(locale, 'ui.editCourseTitle') : t(locale, 'ui.createCourseTitle');
  return <><Heading title={title} description={options.semester ? `${t(locale, 'ui.activeSemester')}: ${localizedSemesterName(locale, options.semester.name)}` : t(locale, 'ui.chooseStaffAndModules')} />
    <form onSubmit={submit} className={`${card} grid gap-5 p-5 sm:grid-cols-2 sm:p-7`}><Notice error>{error}</Notice>{!staffMode && <><label className="block sm:col-span-2"><span className="mb-2 block text-xs font-semibold text-slate-700">{t(locale, 'ui.courseName')}</span><input name="courseName" required maxLength={255} defaultValue={course?.courseName} className={inputClass} /></label><label className="block"><span className="mb-2 block text-xs font-semibold text-slate-700">{t(locale, 'ui.class')}</span><input name="classGroup" required maxLength={50} defaultValue={course?.classGroup} className={inputClass} /></label><label className="block"><span className="mb-2 block text-xs font-semibold text-slate-700">{t(locale, 'ui.studentSemester')}</span><input name="targetSemester" type="number" min={1} max={14} required defaultValue={course?.targetSemester ?? 1} className={inputClass} /></label>{!course && <label className="block"><span className="mb-2 block text-xs font-semibold text-slate-700">{t(locale, 'ui.moduleCount')}</span><input name="moduleCount" type="number" min={1} max={16} required defaultValue={8} className={inputClass} /></label>}</>}
      {select('dosenId', t(locale, 'ui.lecturer'), options.dosens ?? [], course?.dosenId)}{select('aslabId', t(locale, 'ui.labAssistant'), options.aslabs ?? [], course?.aslabId ?? options.currentAslabId, !course && Boolean(options.currentAslabId))}{!staffMode && select('laboranId', t(locale, 'ui.labAdministrator'), options.laborans ?? [], course?.laboranId)}
      <div className="flex gap-3 sm:col-span-2"><button className={primary} disabled={pending}>{pending ? t(locale, 'ui.saving') : course ? t(locale, 'ui.saveChanges') : staffMode ? t(locale, 'ui.saveAssignment') : t(locale, 'ui.createCourse')} <Check className="h-4 w-4" /></button><Link href={course ? `/courses/${course.slug}` : '/courses'} className={secondary}>{t(locale, 'ui.cancel')}</Link></div>
    </form>
  </>;
}

function CourseDetail({ data, user }: { data: AnyRecord; user: SessionUser }) {
  const locale = useLocale();
  const router = useRouter();
  const deleteCourse = trpc.courses.delete.useMutation();
  const [deleteError, setDeleteError] = useState('');
  const course = data;
  const isManager = user.role === 'Laboran' || (user.role === 'Dosen' && user.id === course.dosenId) || (user.role === 'Aslab' && user.id === course.aslabId);
  const archived = Boolean(course.isArchived || !course.semesterIsActive);
  const canEditModules = !archived && (user.role === 'Laboran' || (user.role === 'Aslab' && user.id === course.aslabId));
  const publishedCount = course.meetings.filter((meeting: AnyRecord) => meeting.publishedAt).length;
  const dateLocale = locale === 'en' ? 'en-GB' : 'id-ID';
  const attendanceLabel = (status: string | null | undefined, fallback: string) => status ? localizedStatus(locale, status) : fallback;
  const conductedMeetings = course.meetings.filter((meeting: AnyRecord) => meeting.attendancesCount > 0);
  const attendedMeetings = conductedMeetings.filter((meeting: AnyRecord) => ['Hadir', 'H'].includes(String(meeting.studentAttendanceStatus ?? '').toUpperCase()));
  const attendancePercentage = conductedMeetings.length ? Math.round(attendedMeetings.length / conductedMeetings.length * 100) : 0;
  const staff = [
    { name: course.dosenName, id: course.dosenId, label: t(locale, 'ui.lecturerInCharge'), initials: 'DS', showId: true },
    { name: course.laboranName, id: course.laboranId, label: t(locale, 'ui.labAdministrator'), initials: 'LB', showId: false },
    { name: course.aslabName, id: course.aslabId, label: t(locale, 'ui.labAssistantFull'), initials: 'AS', showId: false },
  ];
  return <div className="space-y-6">
    <div className="flex flex-wrap items-center gap-2">
      <Link href={archived ? '/arsip' : '/courses'} className={secondary}><ArrowLeft className="h-4 w-4" /> {t(locale, 'ui.backToDashboard')}</Link>
      {canEditModules && <Link href={`/courses/${course.slug}/modules/edit`} className={primary}>{t(locale, 'ui.manageModules')}</Link>}
      {user.role === 'Laboran' && <>
        {!archived && <><Link href={`/courses/${course.slug}/edit`} className={secondary}>{t(locale, 'ui.editCourse')}</Link><Link href={`/courses/${course.slug}/staff`} className={secondary}>{t(locale, 'ui.staff')}</Link></>}
        <button type="button" className="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60" disabled={deleteCourse.isPending}
          onClick={() => { void confirmAppAction(t(locale, 'ui.deleteCourseConfirm'), copy(locale, 'ui.deleteCourseText', { name: course.courseName }), 'warning', locale).then((confirmed) => {
            if (!confirmed) return;
            setDeleteError('');
            deleteCourse.mutate({ slug: course.slug }, {
              onSuccess: async () => {
                await showAppAlert('success', t(locale, 'ui.courseDeletedTitle'), t(locale, 'ui.courseDeletedText'), locale);
                router.replace('/courses');
              },
              onError: (reason) => setDeleteError(localizeServerMessage(locale, reason.message)),
            });
          }); }}>
          {deleteCourse.isPending ? <span className="h-4 w-4 animate-spin rounded-full border-2 border-rose-300 border-t-rose-700" /> : <Trash2 className="h-4 w-4" />}
          {deleteCourse.isPending ? t(locale, 'ui.deleting') : t(locale, 'ui.deleteCourse')}
        </button>
      </>}
    </div>
    <Notice error>{deleteError}</Notice>
    {archived && <Notice>{t(locale, 'ui.courseArchivedReadOnly')}</Notice>}
    <Heading title={`${t(locale, 'ui.practicumDetails')}: ${course.courseName}`} description={`${localizedSemesterName(locale, course.semesterName)} · ${t(locale, 'ui.class')} ${course.classGroup}`} />
    {canViewEnrollmentCode(user.role) && course.enrollmentCode && <p className="-mt-4 inline-flex max-w-full flex-wrap items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm"><span className="font-medium text-slate-600">{t(locale, 'ui.enrollment')}</span><code className="break-all font-mono font-bold text-emerald-900">{course.enrollmentCode}</code></p>}
    {course.finalTask && <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
      <div className="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
        <div className="min-w-0 flex-1"><div className="mb-3 flex flex-wrap items-center gap-3"><span className="rounded-lg bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800">{locale === 'en' ? 'FINAL ASSIGNMENT' : 'TUGAS PUNCAK'}</span><h2 className="text-xl font-semibold">{t(locale, 'ui.finalAssignment')}</h2></div><p className="max-w-2xl whitespace-pre-wrap text-sm leading-relaxed text-slate-600">{course.finalTask.description}</p>{course.finalTask.deadline && <p className="mt-4 text-xs font-semibold text-slate-500">{t(locale, 'ui.finalDeadline')}: {new Date(course.finalTask.deadline).toLocaleString(dateLocale)}</p>}</div>
        <div className="w-full md:w-auto">{user.role === 'Mahasiswa' ? <Link href={`/mahasiswa/final-tasks/${course.finalTask.id}`} className={primary}>{t(locale, 'ui.manageFinalReport')}</Link> : <Link href={`/final-tasks/${course.finalTask.id}`} className={secondary}>{t(locale, 'ui.reviewSubmissions')}</Link>}</div>
      </div>
    </section>}
    <details className={`${card} p-4 sm:p-6`}>
      <summary className="cursor-pointer rounded-lg text-sm font-semibold text-emerald-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700">{t(locale, 'ui.courseInformationAndParticipants')}</summary>
      <div className="mt-5 border-t border-slate-100 pt-5">
        <h2 className="mb-5 text-xs font-semibold uppercase tracking-wide text-slate-500">{t(locale, 'ui.courseInformation')}</h2>
        <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">{staff.map((member) => <div key={member.label} className="flex min-w-0 items-center gap-4">
          <span className="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 text-sm font-bold text-slate-500">{String(member.name ?? member.initials).slice(0, 2).toUpperCase()}</span>
          <div className="min-w-0"><p className="mb-1 text-xs font-semibold text-slate-500">{member.label}</p><p className="break-words text-sm font-semibold text-slate-900">{member.name ?? '—'}</p>{member.showId && member.id && <p className="mt-0.5 break-all text-xs font-semibold italic text-emerald-700">NIP: {member.id}</p>}</div>
        </div>)}</div>
        {user.role === 'Mahasiswa' ? <div className="mt-7 border-t border-slate-100 pt-6">
          <h3 className="mb-4 text-xs font-semibold uppercase tracking-wide text-slate-500">{t(locale, 'ui.yourAttendanceStatistics')}</h3>
          <div className="rounded-2xl border border-slate-100 bg-slate-50 p-5">
            <div className="mb-3 flex items-end justify-between"><span className="text-xs font-semibold text-slate-700">{t(locale, 'ui.totalPresent')}</span><span className="text-2xl font-semibold text-emerald-700">{attendancePercentage}%</span></div>
            <div className="mb-3 h-2 overflow-hidden rounded-full bg-slate-200"><div className={`h-full rounded-full ${attendancePercentage >= 75 ? 'bg-emerald-500' : attendancePercentage >= 50 ? 'bg-amber-500' : 'bg-rose-500'}`} style={{ width: `${attendancePercentage}%` }} /></div>
            <p className="text-right text-xs font-semibold text-slate-500">{copy(locale, 'ui.meetingsOf', { present: attendedMeetings.length, total: conductedMeetings.length })}</p>
          </div>
        </div> : <div className="mt-7 border-t border-slate-100 pt-6"><Link href={`/courses/${course.slug}/attendance-report`} className={`${secondary} w-full justify-center`}>{t(locale, 'ui.laprakSummary')}</Link></div>}
        {isManager && <Link href={`/courses/${course.slug}/students`} className={`${secondary} mt-3 w-full justify-center`}>{t(locale, 'ui.participantsInCourse')}</Link>}
        {user.role === 'Mahasiswa' && <Link href={`/courses/${course.slug}/print-card`} target="_blank" className={`${secondary} mt-3 w-full justify-center`}><Printer className="h-4 w-4" />{t(locale, 'ui.printPracticumCard')}</Link>}
      </div>
    </details>
    <section>
      <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div><h2 className="text-lg font-semibold text-slate-900">{t(locale, 'ui.moduleOverview')}</h2><p className="mt-1 text-sm text-slate-500">{copy(locale, 'ui.openedModules', { opened: publishedCount, total: course.meetings.length })}</p></div>
        <div className="flex flex-wrap items-center gap-3">
          <span className="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-800">{copy(locale, 'ui.moduleCountBadge', { count: course.meetings.length })}</span>
          {canEditModules && <Link href={`/courses/${course.slug}/modules/edit`} className={secondary}>{t(locale, 'ui.manageModules')}</Link>}
        </div>
      </div>
      <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        {course.meetings.map((meeting: AnyRecord) => {
          const published = Boolean(meeting.publishedAt);
          const submission = meeting.studentSubmission as AnyRecord | null;
          const statuses = submission ? [submission.aslabStatus, submission.laboranStatus, ...(submission.isFinal ? [submission.dosenStatus] : [])] : [];
          const studentStatus = !submission ? 'Belum mengumpulkan' : statuses.includes('Ditolak') ? 'Ditolak' : statuses.includes('Revisi') ? 'Revisi' : submission.isCompleted ? 'Diterima' : 'Menunggu pemeriksaan';
          const canResubmit = studentStatus === 'Revisi' || studentStatus === 'Ditolak';
          const pastDeadline = Boolean(meeting.deadline && new Date(meeting.deadline).getTime() < new Date(course.evaluatedAt).getTime());
          const statusColor = studentStatus === 'Diterima' ? 'border-emerald-100 bg-emerald-50 text-emerald-800' : studentStatus === 'Revisi' ? 'border-amber-100 bg-amber-50 text-amber-800' : studentStatus === 'Ditolak' ? 'border-rose-100 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-600';
          return <article key={meeting.id} className={`flex min-w-0 flex-col rounded-2xl border p-6 shadow-sm ${published ? 'border-slate-200 bg-white' : 'border-dashed border-slate-300 bg-slate-50/60'}`}>
            <div className="mb-3 flex items-start justify-between gap-3">
              <span className="text-sm font-semibold text-emerald-700">{t(locale, 'ui.module')} {meeting.meetingNumber}</span>
              {!published ? <span className="rounded-full bg-slate-200/80 px-2.5 py-1 text-xs font-semibold text-slate-600">{t(locale, 'ui.comingSoon')}</span>
                : user.role === 'Mahasiswa' ? <span className={`rounded-full border px-2.5 py-1 text-xs font-semibold ${submission ? statusColor : 'border-slate-200 bg-slate-50 text-slate-600'}`}>{t(locale, studentStatus === 'Diterima' ? 'ui.accepted' : studentStatus === 'Ditolak' ? 'ui.rejected' : studentStatus === 'Revisi' ? 'ui.revision' : studentStatus === 'Menunggu pemeriksaan' ? 'ui.awaitingReview' : 'ui.notSubmitted')}</span>
                  : <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800">{t(locale, 'ui.moduleAvailable')}</span>}
            </div>
            <h3 className={`text-lg font-semibold ${published ? 'text-slate-900' : 'text-slate-700'}`}>{localizedModuleTitle(locale, meeting.title, meeting.meetingNumber)}</h3>
            {published ? <>
              <p className="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">{meeting.description || t(locale, 'ui.reportOpenForStudents')}</p>
              <p className="mt-4 text-xs text-slate-500">{t(locale, 'ui.deadline')}: {meeting.deadline ? `${new Date(meeting.deadline).toLocaleString(dateLocale)} ${locale === 'en' ? 'WIB' : 'WIB'}` : t(locale, 'ui.noDeadline')}</p>
              {user.role === 'Mahasiswa' && <>
                <p className="mt-3 text-xs text-slate-600">{t(locale, 'ui.attendance')}: {attendanceLabel(meeting.studentAttendanceStatus, meeting.attendancesCount ? t(locale, 'ui.noExplanation') : t(locale, 'ui.noAttendance'))}</p>
                <div className={`mt-4 rounded-xl border p-3 text-sm ${submission ? statusColor : pastDeadline ? 'border-rose-100 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-600'}`}>
                  {submission ? <><p className="font-semibold">{localizedStatus(locale, studentStatus)}</p><p className="mt-1 text-xs">{t(locale, 'role.Aslab')}: {localizedStatus(locale, submission.aslabStatus)} · {t(locale, 'role.Laboran')}: {localizedStatus(locale, submission.laboranStatus)}</p>{canResubmit && <p className="mt-2 text-xs">{t(locale, 'ui.note')}: {submission.latestFeedback || t(locale, 'ui.openAssignmentDetails')}</p>}</>
                    : <p>{pastDeadline ? t(locale, 'ui.deadlinePassed') : t(locale, 'ui.reportNotUploaded')}</p>}
                </div>
              </>}
            </> : <>
              <p className="mt-3 text-sm leading-6 text-slate-500">{locale === 'en' ? 'Submissions are not open yet. Materials and instructions can be added later.' : 'Pengumpulan belum dibuka. Detail materi dan instruksi dapat ditambahkan kemudian.'}</p>
              <div className="mt-5 space-y-2" aria-hidden="true"><div className="h-2 w-4/5 rounded-full bg-slate-200" /><div className="h-2 w-3/5 rounded-full bg-slate-200/70" /></div>
            </>}
            <div className="mt-auto flex flex-wrap gap-3 pt-5">
              {published && user.role === 'Mahasiswa' && <Link href={`/mahasiswa/meetings/${meeting.id}/submission`} className={primary}>{archived || submission?.isCompleted ? t(locale, 'ui.viewReport') : canResubmit ? t(locale, 'ui.uploadRevision') : submission ? t(locale, 'ui.viewStatus') : t(locale, 'ui.uploadReport')}</Link>}
              {published && isManager && <><Link href={`/meetings/${meeting.id}/submissions`} className={primary}>{copy(locale, 'ui.reportsCount', { count: meeting.submissionsCount })}</Link><Link href={`/meetings/${meeting.id}/attendance`} className={secondary}>{t(locale, 'ui.attendance')}</Link></>}
              {published && meeting.moduleDriveLink && <a href={meeting.moduleDriveLink} target="_blank" rel="noopener noreferrer" className={secondary}>{t(locale, 'ui.viewMaterial')}</a>}
              {!published && canEditModules && <Link href={`/courses/${course.slug}/modules/edit`} className={primary}>{t(locale, 'ui.configureModule')}</Link>}
            </div>
          </article>;
        })}
        {!course.meetings.length && <p className="col-span-full rounded-2xl border border-dashed border-slate-200 p-10 text-center text-sm text-slate-500">{t(locale, 'ui.noModuleStructure')}</p>}
      </div>
    </section>
  </div>;
}
function PracticumCard({ data }: { data: AnyRecord }) {
  const locale = useLocale();
  const { course, student, meetings } = data;
  const byNumber = new Map<number, AnyRecord>(meetings.map((row: AnyRecord) => [row.meeting.meetingNumber, row]));
  return <div className="mx-auto max-w-5xl">
    <Heading title={t(locale, 'ui.printCard')} description={`${course.courseName} · ${t(locale, 'ui.class')} ${course.classGroup}`} actions={<button className={`${primary} print:hidden`} onClick={() => window.print()}><Printer className="h-4 w-4" /> {t(locale, 'ui.printSavePdf')}</button>} />
    <article id="practicum-card" className="bg-white p-5 text-black shadow-sm sm:p-8 print:shadow-none">
      <header className="border-b-4 border-double border-black pb-4 text-center">
        <p className="text-xs font-bold uppercase">{t(locale, 'ui.universityName')}</p>
        <p className="mt-1 text-xs font-bold uppercase">{t(locale, 'ui.facultyName')}</p>
        <p className="mt-1 text-sm font-bold uppercase">{t(locale, 'ui.computerLabName')}</p>
        <p className="mt-1 text-[10px]">Jl. Lap. Golf, Desa Durian Jangak, Kec. Pancur Batu, Kabupaten Deli Serdang</p>
      </header>
      <h2 className="my-5 text-center text-base font-bold uppercase">{t(locale, 'ui.practicumCardHeading')}</h2>
      <section className="grid gap-4 sm:grid-cols-[1fr_3cm]">
        <dl className="grid grid-cols-[6rem_1rem_1fr] gap-y-2 text-sm"><dt className="font-bold">{t(locale, 'ui.name')}</dt><dd>:</dd><dd className="font-semibold uppercase">{student.name}</dd><dt className="font-bold">NIM</dt><dd>:</dd><dd>{student.id}</dd><dt className="font-bold">{t(locale, 'ui.courseSubject')}</dt><dd>:</dd><dd>{course.courseName}</dd><dt className="font-bold">{t(locale, 'ui.class')}</dt><dd>:</dd><dd>{course.classGroup}</dd><dt className="font-bold">{t(locale, 'ui.semester')}</dt><dd>:</dd><dd>{localizedSemesterName(locale, course.semesterName)}</dd></dl>
        <div className="flex h-40 w-28 items-center justify-center border border-black text-xs">{student.avatar ? <Image src="/api/avatar" alt={student.name} width={112} height={160} unoptimized className="h-full w-full object-cover" /> : '3 × 4'}</div>
      </section>
      <table className="mt-6 w-full border-collapse text-center text-xs">
        <thead><tr><th rowSpan={2} className="border border-black p-2">No</th><th rowSpan={2} className="border border-black p-2">{locale === 'en' ? 'Practicum date' : 'Tanggal praktikum'}</th><th rowSpan={2} className="border border-black p-2">{locale === 'en' ? 'Practicum title' : 'Judul praktikum'}</th><th colSpan={2} className="border border-black p-2">{locale === 'en' ? 'Lab assistant verification' : 'Verifikasi asisten lab'}</th></tr><tr><th className="border border-black p-2">{locale === 'en' ? 'Attendance' : 'Absensi'}</th><th className="border border-black p-2">{locale === 'en' ? 'Report acceptance' : 'Penerimaan laporan'}</th></tr></thead>
        <tbody>{Array.from({ length: 8 }, (_, index) => {
          const number = index + 1; const row = byNumber.get(number); const status = String(row?.attendanceStatus ?? '').toUpperCase();
          const attendance = ['HADIR', 'H'].includes(status) ? t(locale, 'ui.present') : ['SAKIT', 'S'].includes(status) ? t(locale, 'ui.sick') : ['IZIN', 'I'].includes(status) ? t(locale, 'ui.permitted') : ['TK', 'ALPHA', 'TANPA KETERANGAN'].includes(status) ? 'TK' : '';
          const dateLocale = locale === 'en' ? 'en-GB' : 'id-ID';
          return <tr key={number}><td className="h-8 border border-black">{number}</td><td className="border border-black">{row ? new Date(row.meeting.createdAt).toLocaleDateString(dateLocale) : ''}</td><td className="border border-black px-2 text-left">{row ? localizedModuleTitle(locale, row.meeting.title, number) : `${t(locale, 'ui.module')} ${number}`}</td><td className="border border-black">{attendance}</td><td className="border border-black">{row?.isCompleted ? new Date(row.aslabAccAt ?? row.updatedAt).toLocaleDateString(dateLocale) : ''}</td></tr>;
        })}</tbody>
      </table>
      <table className="mt-6 w-full border-collapse text-sm"><tbody><tr><td className="border border-black p-2">{t(locale, 'ui.approvedBy')}<br />{t(locale, 'ui.labAdministrator')}</td><td className="border border-black p-2">{t(locale, 'ui.acknowledgedBy')}<br />{t(locale, 'ui.lecturerRole')}</td></tr><tr><td className="h-24 border border-black" /><td className="border border-black" /></tr><tr><td className="border border-black p-2 font-semibold underline">{course.laboranName}<br />NIP: {course.laboranId}</td><td className="border border-black p-2 font-semibold underline">{course.dosenName}<br />NIP: {course.dosenId}</td></tr></tbody></table>
      <p className="mt-5 text-[10px] italic">{t(locale, 'ui.deadlineFootnote')}</p>
    </article>
  </div>;
}

function AttendanceEditor({ rows, meetingId }: { rows: AnyRecord[]; meetingId: number }) {
  const locale = useLocale();
  const mutation = trpc.attendance.save.useMutation(); const action = useAction(mutation);
  const [values, setValues] = useState<Record<string, string>>(() => Object.fromEntries(rows.map((row) => [row.studentId, row.status ?? 'Hadir'])));
  const today = new Date(); const date = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
  const statuses = ['Hadir', 'Sakit', 'Izin', 'Tanpa Keterangan'];
  return <><Heading title={t(locale, 'ui.attendanceStudentsTitle')} description={t(locale, 'ui.attendanceDescription')} /><div className={`${card} overflow-hidden`}><div className="overflow-x-auto"><table className="w-full min-w-[650px] text-left text-sm"><thead className="bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-5 py-4">{t(locale, 'ui.id')}</th><th className="px-5 py-4">{t(locale, 'ui.nameStudent')}</th><th className="px-5 py-4">{t(locale, 'ui.status')}</th></tr></thead><tbody className="divide-y divide-slate-100">{rows.map((row) => <tr key={row.studentId}><td className="px-5 py-3 font-mono text-xs">{row.studentId}</td><td className="px-5 py-3 font-semibold">{row.name}</td><td className="px-5 py-3"><select value={values[row.studentId]} onChange={(event) => setValues((previous) => ({ ...previous, [row.studentId]: event.target.value }))} className="rounded-lg border border-slate-200 px-3 py-2 text-sm">{statuses.map((status) => <option key={status} value={status}>{localizedStatus(locale, status)}</option>)}</select></td></tr>)}</tbody></table></div><div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 p-4"><Notice>{action.message}</Notice><button className={primary} disabled={action.pending} onClick={() => action.run({ meetingId, date, rows: rows.map((row) => ({ studentId: row.studentId, status: values[row.studentId] as 'Hadir' | 'Sakit' | 'Izin' | 'Tanpa Keterangan' })) }, t(locale, 'ui.attendanceSaved'))}>{t(locale, 'ui.saveAttendance')} <Check className="h-4 w-4" /></button></div><Notice error>{action.error}</Notice></div></>;
}

function StudentSubmission({ data, final = false }: { data: AnyRecord; final?: boolean }) {
  const locale = useLocale();
  const submission = data.submission; const task = data.task; const mutation = trpc.submissions.submit.useMutation(); const finalMutation = trpc.submissions.submitFinal.useMutation(); const revise = trpc.submissions.revise.useMutation();
  const router = useRouter(); const [link, setLink] = useState(submission?.submissionLink ?? ''); const [notes, setNotes] = useState(submission?.notes ?? ''); const [error, setError] = useState(''); const [message, setMessage] = useState('');
  const status = submission?.isCompleted ? 'Diterima' : [submission?.aslabStatus, submission?.laboranStatus, submission?.dosenStatus].includes('Ditolak') ? 'Ditolak' : [submission?.aslabStatus, submission?.laboranStatus, submission?.dosenStatus].includes('Revisi') ? 'Revisi' : submission ? 'Menunggu pemeriksaan' : 'Belum dikumpulkan';
  const canResubmit = status === 'Revisi' || status === 'Ditolak'; const deadline = task.deadline ? new Date(task.deadline) : null; const closed = deadline && deadline < new Date() && !canResubmit;
  const history = data.histories ?? [];
  function submit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); setError(''); setMessage(''); const onSuccess = (result: AnyRecord) => { setMessage(localizeServerMessage(locale, result.message)); router.refresh(); }; const onError = (reason: { message: string }) => setError(localizeServerMessage(locale, reason.message));
    if (submission) revise.mutate({ id: submission.id, documentVersion: submission.documentVersion, driveLink: link, notes: notes || null }, { onSuccess, onError });
    else if (final) finalMutation.mutate({ finalTaskId: task.id, driveLink: link, notes: notes || null }, { onSuccess, onError });
    else mutation.mutate({ meetingId: task.id, driveLink: link, notes: notes || null }, { onSuccess, onError });
  }
  const pending = mutation.isPending || finalMutation.isPending || revise.isPending;
  const dateLocale = locale === 'en' ? 'en-GB' : 'id-ID';
  const title = final ? t(locale, 'ui.submitFinalReport') : copy(locale, 'ui.submitModule', { number: task.meetingNumber });
  const localizedTaskTitle = task.title ? localizedModuleTitle(locale, task.title, task.meetingNumber) : '';
  const statusLabel = t(locale, status === 'Diterima' ? 'ui.accepted' : status === 'Ditolak' ? 'ui.rejected' : status === 'Revisi' ? 'ui.revision' : status === 'Menunggu pemeriksaan' ? 'ui.awaitingReview' : 'ui.notSubmitted');
  return <><Heading title={title} description={`${data.course.courseName} · ${t(locale, 'ui.class')} ${data.course.classGroup}`} />
    {localizedTaskTitle && <p className="mb-4 text-sm font-semibold text-slate-700">{localizedTaskTitle}</p>}
    <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]"><section className={`${card} p-5 sm:p-7`}><div className="flex flex-wrap items-center justify-between gap-3"><h2 className="text-lg font-bold">{t(locale, 'ui.submissionStatus')}</h2><span className={`rounded-full px-3 py-1.5 text-xs font-bold ${status === 'Diterima' ? 'bg-emerald-50 text-emerald-800' : status === 'Revisi' || status === 'Ditolak' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-800'}`}>{statusLabel}</span></div>{task.description && <p className="mt-4 whitespace-pre-wrap text-sm leading-7 text-slate-600">{task.description}</p>}{deadline && <p className={`mt-4 text-sm font-semibold ${closed ? 'text-rose-700' : 'text-slate-500'}`}>{t(locale, 'ui.deadline')}: {deadline.toLocaleString(dateLocale)} {closed ? `(${t(locale, 'ui.closedSubmission')})` : ''}</p>}
      {submission && <div className="mt-5 rounded-xl bg-slate-50 p-4"><p className="text-xs text-slate-500">{t(locale, 'ui.documentVersion')} {submission.documentVersion}</p>{submission.submissionLink && <a href={submission.submissionLink} target="_blank" rel="noreferrer" className="mt-2 block break-all text-sm font-semibold text-emerald-700 underline">{t(locale, 'ui.openSubmittedReport')}</a>}{submission.notes && <p className="mt-2 text-sm text-slate-600">{t(locale, 'review.studentNote')}: {submission.notes}</p>}</div>}
      {(!submission || !submission.isCompleted) && <form onSubmit={submit} className="mt-6 space-y-4"><Notice error>{error}</Notice><Notice>{message}</Notice><label className="block"><span className="mb-2 block text-xs font-semibold text-slate-700">{t(locale, 'ui.driveFileLink')}</span><input type="url" value={link} onChange={(event) => setLink(event.target.value)} required disabled={Boolean(closed)} className={inputClass} placeholder="https://drive.google.com/file/d/.../view" /></label><label className="block"><span className="mb-2 block text-xs font-semibold text-slate-700">{t(locale, 'ui.studentNoteForReviewer')}</span><textarea value={notes} onChange={(event) => setNotes(event.target.value)} rows={4} disabled={Boolean(closed)} className={inputClass} /></label><button className={primary} disabled={pending || Boolean(closed)}>{submission ? t(locale, 'ui.resubmitVersion') : t(locale, 'ui.sendReport')} <ArrowRight className="h-4 w-4" /></button></form>}
    </section><section className={`${card} p-5 sm:p-7`}><h2 className="text-lg font-bold">{t(locale, 'ui.reviewHistory')}</h2>{history.length ? <ol className="mt-5 space-y-4">{history.map((item: AnyRecord) => <li key={item.history.id} className="border-l-2 border-emerald-200 pl-4"><div className="flex items-center justify-between gap-3"><p className="text-sm font-semibold text-slate-800">{item.history.actionType} · {t(locale, 'ui.documentVersion').toLowerCase()} {item.history.documentVersion}</p><span className="text-xs text-slate-400">{new Date(item.history.createdAt).toLocaleDateString(dateLocale)}</span></div>{item.reviewerName && <p className="mt-1 text-xs text-slate-500">{item.reviewerName}</p>}{item.history.feedback && <p className="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{item.history.feedback}</p>}</li>)}</ol> : <p className="mt-4 text-sm text-slate-500">{t(locale, 'ui.noReviewHistory')}</p>}</section></div>
  </>;
}

function ReviewPanel({ data, user }: { data: AnyRecord; user: SessionUser }) {
  const locale = useLocale();
  const submission = data.submission as AnyRecord;
  const mutation = trpc.submissions.review.useMutation();
  const score = trpc.submissions.setScore.useMutation();
  const [status, setStatus] = useState<'ACC' | 'REVISI' | 'DITOLAK' | null>(null);
  const [notes, setNotes] = useState('');
  const [scoreValue, setScoreValue] = useState('');
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const router = useRouter();
  const archived = Boolean(data.isArchived || !data.semesterIsActive);
  const canReview = !archived && !submission.isCompleted && (
    (user.role === 'Aslab' && submission.aslabStatus !== 'ACC') ||
    (user.role === 'Laboran' && submission.aslabStatus === 'ACC' && submission.laboranStatus !== 'ACC') ||
    (submission.isFinal && user.role === 'Dosen' && submission.aslabStatus === 'ACC' && submission.laboranStatus === 'ACC' && submission.dosenStatus !== 'ACC')
  );
  const scoreField = user.role === 'Aslab' ? 'aslabScore' : 'laboranScore';
  const existingScore = submission[scoreField];
  const canScore = !archived && !submission.isFinal && submission.isCompleted && (user.role === 'Aslab' || user.role === 'Laboran');
  const defaultModuleTitle = `Modul ${data.meetingNumber}`;
  const taskTitle = locale === 'en' && data.taskTitle === defaultModuleTitle ? `${t(locale, 'review.moduleTitle')} ${data.meetingNumber}` : data.taskTitle;
  const title = submission.isFinal ? t(locale, 'review.finalReport') : `${t(locale, 'review.moduleTitle')} ${data.meetingNumber}: ${taskTitle}`;
  const scoreRole = user.role === 'Aslab' ? t(locale, 'role.Aslab') : user.role === 'Laboran' ? t(locale, 'role.Laboran') : t(locale, 'role.Dosen');
  const scoreHeading = t(locale, 'review.scoreHeading').replace('{role}', scoreRole).replace('{number}', String(data.meetingNumber));
  const dateLocale = locale === 'en' ? 'en-GB' : 'id-ID';
  const formatDateTime = (value: string | Date) => new Intl.DateTimeFormat(dateLocale, {
    dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta',
  }).format(new Date(value));
  const statusText = (value: string) => {
    switch (value) {
      case 'Pending': return t(locale, 'review.statusPending');
      case 'Revisi': return t(locale, 'review.statusRevision');
      case 'Ditolak': return t(locale, 'review.statusRejected');
      case 'ACC': return t(locale, 'review.statusApproved');
      case 'N/A': return t(locale, 'review.statusNotApplicable');
      default: return value;
    }
  };
  const actionText = (value: string) => {
    switch (value) {
      case 'Upload': return t(locale, 'review.actionUpload');
      case 'Revision': return t(locale, 'review.actionRevision');
      case 'Rejected': return t(locale, 'review.actionRejected');
      case 'ACC': return t(locale, 'review.actionApproved');
      default: return value;
    }
  };
  const serverErrorText = (value: string) => {
    if (locale !== 'en') return value;
    const messages: Record<string, string> = {
      'Laporan telah selesai diverifikasi.': t(locale, 'review.errorCompleted'),
      'Versi dokumen berubah. Muat ulang halaman sebelum memeriksa.': t(locale, 'review.errorVersion'),
      'Nilai wajib diisi saat memberikan ACC.': t(locale, 'review.scoreRequiredError'),
      'Laporan sudah mendapat ACC Aslab.': t(locale, 'review.errorAslabApproved'),
      'Menunggu verifikasi Aslab.': t(locale, 'review.errorWaitingAslab'),
      'Laporan sudah mendapat ACC Laboran.': t(locale, 'review.errorLaboranApproved'),
      'Menunggu verifikasi Aslab dan Laboran.': t(locale, 'review.errorWaitingReviewers'),
      'Laporan sudah mendapat ACC Dosen.': t(locale, 'review.errorLecturerApproved'),
      'Kelas arsip hanya dapat dibaca.': t(locale, 'review.errorArchived'),
      'Nilai modul dapat diberikan setelah laporan mingguan selesai diverifikasi.': t(locale, 'review.errorScoreStage'),
    };
    return messages[value] ?? t(locale, 'review.saveError');
  };
  const drivePreview = (value: string) => {
    try {
      const url = new URL(value);
      if (url.pathname === '/open' || url.pathname === '/uc') {
        const fileId = url.searchParams.get('id');
        if (!fileId) return value;
        url.pathname = `/file/d/${fileId}/preview`;
        url.search = '';
        return url.toString();
      }
      if (!url.pathname.startsWith('/file/d/')) return value;
      url.pathname = url.pathname.replace(/\/(?:view|edit|preview)\/?$/, '');
      url.pathname = `${url.pathname}/preview`;
      return url.toString();
    } catch {
      return value;
    }
  };
  const documentUrl = submission.filePath ? `/submissions/${submission.id}/file` : submission.submissionLink;
  const previewUrl = documentUrl ? (submission.filePath ? documentUrl : drivePreview(documentUrl)) : null;

  function review(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError('');
    setMessage('');
    if (!status) {
      setError(t(locale, 'review.chooseDecisionError'));
      return;
    }
    if (!submission.isFinal && status === 'ACC' && scoreValue.trim() === '') {
      setError(t(locale, 'review.scoreRequiredError'));
      return;
    }
    mutation.mutate({
      id: submission.id,
      documentVersion: submission.documentVersion,
      status,
      ...(submission.isFinal ? { notes: notes || null } : { feedback: notes || null }),
      ...(!submission.isFinal && status === 'ACC' ? { score: Number(scoreValue) } : {}),
    }, {
      onSuccess: () => { setMessage(t(locale, 'review.saved')); setError(''); router.refresh(); },
      onError: (reason) => setError(serverErrorText(reason.message)),
    });
  }

  return <>
    <Heading
      title={title}
      description={`${data.courseName} (${data.classGroup})`}
      actions={<Link href={`/courses/${data.slug}`} className={secondary}><ArrowLeft className="h-4 w-4" /> {t(locale, 'review.backToClass')} {data.courseName}</Link>}
    />
    {data.taskDescription && <p className="mb-5 whitespace-pre-line text-sm leading-7 text-slate-600">{data.taskDescription}</p>}
    <div className="grid min-w-0 items-start gap-4 sm:gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(18rem,1fr)]">
      <section className={`${card} min-w-0 overflow-hidden`}>
        {previewUrl ? <div className="h-[65svh] min-h-64 max-h-[40rem] w-full bg-slate-100 sm:h-[70svh] sm:min-h-[28rem] xl:h-[72vh] xl:min-h-[32rem]">
          <iframe title={title} src={previewUrl} className="block h-full w-full border-0" allow="autoplay" />
        </div> : <div className="flex min-h-64 items-center justify-center bg-slate-50 p-8 text-center text-sm text-slate-500">{t(locale, 'review.previewUnavailable')}</div>}
        {documentUrl && <div className="border-t border-slate-100 p-3 sm:p-4"><a href={documentUrl} target="_blank" rel="noopener noreferrer" className={`${secondary} max-w-full whitespace-normal text-left`}><FileText className="h-4 w-4 shrink-0" /> {t(locale, 'review.openDocument')}</a></div>}
      </section>
      <div className="min-w-0 space-y-5">
        <section className={`${card} p-5 sm:p-6`}>
          <h2 className="mb-4 text-lg font-semibold">{data.student?.name ?? submission.studentId}</h2>
          <p className="mb-4 text-sm text-slate-500">{t(locale, 'review.deadline')}: {data.deadline ? `${formatDateTime(data.deadline)} ${t(locale, 'review.timeZone')}` : t(locale, 'review.noDeadline')}</p>
          <div className="mb-5 space-y-3 text-sm">
            <span className={`inline-flex rounded-full border px-3 py-1.5 font-semibold ${submission.isCompleted ? 'border-emerald-100 bg-emerald-50 text-emerald-800' : 'border-amber-100 bg-amber-50 text-amber-800'}`}>{submission.isCompleted ? t(locale, 'review.approved') : t(locale, 'review.awaiting')}</span>
            <p>{t(locale, 'review.documentVersion')}: <strong>{submission.documentVersion}</strong></p>
            <p>{t(locale, 'role.Aslab')}: {statusText(submission.aslabStatus)} · {t(locale, 'role.Laboran')}: {statusText(submission.laboranStatus)}</p>
            {submission.isFinal && <p>{t(locale, 'role.Dosen')}: {statusText(submission.dosenStatus)}</p>}
            {data.student?.email && <p className="text-slate-500">{data.student.email}</p>}
          </div>
          {submission.notes && <div className="mb-5 rounded-xl bg-slate-50 p-4"><p className="text-xs font-semibold text-slate-500">{t(locale, 'review.studentNote')}</p><p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-slate-700">{submission.notes}</p></div>}
          <Notice error>{error}</Notice><Notice>{message}</Notice>
          {canReview ? <form onSubmit={review} className="mt-5 space-y-4 border-t border-slate-100 pt-5">
            <div className="flex flex-wrap gap-3">
              <button type="button" aria-pressed={status === 'ACC'} onClick={() => { setStatus('ACC'); setError(''); }} className={status === 'ACC' ? primary : secondary}>{t(locale, 'review.approve')}</button>
              <button type="button" aria-pressed={status === 'REVISI'} onClick={() => { setStatus('REVISI'); setScoreValue(''); setError(''); }} className={status === 'REVISI' ? 'inline-flex items-center justify-center rounded-xl bg-amber-100 px-4 py-2.5 text-sm font-semibold text-amber-900' : secondary}>{t(locale, 'review.requestRevision')}</button>
              <button type="button" aria-pressed={status === 'DITOLAK'} onClick={() => { setStatus('DITOLAK'); setScoreValue(''); setError(''); }} className={status === 'DITOLAK' ? 'inline-flex items-center justify-center rounded-xl bg-rose-100 px-4 py-2.5 text-sm font-semibold text-rose-800' : secondary}>{t(locale, 'review.reject')}</button>
            </div>
            <label className="block text-sm font-semibold">{t(locale, 'review.feedback')} <span className="font-normal text-slate-500">({t(locale, 'review.optional')})</span>
              <textarea rows={4} maxLength={5000} className={inputClass} value={notes} onChange={(event) => setNotes(event.target.value)} />
            </label>
            {!submission.isFinal && <div>
              <label className="block text-sm font-semibold" htmlFor="review-score">{t(locale, 'review.moduleScore')}</label>
              <input id="review-score" type="number" step="0.01" min="0" max="100" required={status === 'ACC'} disabled={status !== 'ACC'} value={scoreValue} onChange={(event) => setScoreValue(event.target.value)} className={`${inputClass} mt-2 max-w-40 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400 disabled:opacity-70`} />
              <p className="mt-1 text-xs text-slate-500">{status === 'ACC' ? t(locale, 'review.scoreFillHint') : t(locale, 'review.scoreEnableHint')}</p>
            </div>}
            <button className={primary} disabled={mutation.isPending || status === null}>{t(locale, 'review.saveReview')} <Check className="h-4 w-4" /></button>
          </form> : <p className="mt-5 border-t border-slate-100 pt-5 text-sm text-slate-500">{archived ? t(locale, 'review.archivedReadOnly') : submission.isCompleted ? t(locale, 'review.complete') : t(locale, 'review.waitingPrevious')}</p>}
          {canScore && <section className="mt-6 border-t border-slate-100 pt-5">
            <h3 className="font-semibold text-slate-900">{scoreHeading}</h3>
            {existingScore === null || existingScore === undefined ? <>
              <p className="mt-1 text-xs leading-5 text-amber-700">{t(locale, 'review.scoreLegacyMissing')}</p>
              <form className="mt-3 flex flex-wrap items-end gap-3" onSubmit={(event) => {
                event.preventDefault();
                score.mutate({ id: submission.id, score: Number(scoreValue) }, {
                  onSuccess: () => { setMessage(t(locale, 'review.saved')); router.refresh(); },
                  onError: (reason) => setError(serverErrorText(reason.message)),
                });
              }}>
                <label className="text-sm font-semibold">{t(locale, 'review.moduleScore')}<input type="number" step="0.01" min="0" max="100" required value={scoreValue} onChange={(event) => setScoreValue(event.target.value)} className={`${inputClass} mt-1 max-w-32`} /></label>
                <button className={primary} disabled={score.isPending}>{t(locale, 'review.saveScore')}</button>
              </form>
            </> : <p className="mt-2 text-sm text-slate-600">{t(locale, 'review.scoreSaved')} <strong className="text-emerald-800">{existingScore}</strong></p>}
            <p className="mt-3 text-xs text-slate-500">{t(locale, 'review.scoreAslab')}: <strong>{submission.aslabScore ?? '-'}</strong> | {t(locale, 'review.scoreLaboran')}: <strong>{submission.laboranScore ?? '-'}</strong></p>
          </section>}
        </section>
        <section className={`${card} p-5 sm:p-6`}>
          <h2 className="mb-4 font-semibold">{t(locale, 'review.historyTitle')}</h2>
          <div className="space-y-4">
            {data.histories?.map((item: AnyRecord) => <article key={item.history.id} className="rounded-xl bg-slate-50 p-4 text-sm">
              <p className="font-semibold">{t(locale, 'review.document')} v{item.history.documentVersion} · {actionText(item.history.actionType)}</p>
              <p className="mt-1 text-xs text-slate-500">{item.reviewerName ?? t(locale, 'review.studentFallback')} · {formatDateTime(item.history.createdAt)}</p>
              {item.history.feedback && <p className="mt-2 whitespace-pre-line">{item.history.feedback}</p>}
              {item.history.driveLink && <a className="mt-3 inline-block font-semibold text-emerald-700" href={item.history.driveLink} target="_blank" rel="noopener noreferrer">{t(locale, 'review.openHistoryDocument')}</a>}
            </article>)}
            {!data.histories?.length && <p className="text-sm text-slate-500">{t(locale, 'review.noHistory')}</p>}
          </div>
        </section>
      </div>
    </div>
  </>;
}
function TablePage({ title, description, rows, type }: { title: string; description: string; rows: AnyRecord[]; type: 'pending' | 'mine' | 'meeting' }) {
  const locale = useLocale();
  const searchParams = useSearchParams();
  const archived = searchParams.get('archive') === '1';
  const archiveHref = archived ? '/my-submissions' : '/my-submissions?archive=1';
  return <>
    <Heading title={title} description={description} actions={type === 'mine' ? <Link href={archiveHref} className={secondary}>{t(locale, archived ? 'ui.currentSemester' : 'ui.viewArchive')}</Link> : undefined} />
    <div className={`${card} overflow-hidden`}><div className="overflow-x-auto"><table className="w-full min-w-[740px] text-left text-sm">
      <thead className="bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-5 py-4">{t(locale, 'ui.studentTask')}</th><th className="px-5 py-4">{t(locale, 'ui.courseModule')}</th><th className="px-5 py-4">{t(locale, 'ui.status')}</th><th className="px-5 py-4">{t(locale, 'ui.action')}</th></tr></thead>
      <tbody className="divide-y divide-slate-100">{rows.map((row, index) => {
        const submission = row.submission as AnyRecord | null | undefined;
        const isFinal = type === 'mine' ? Boolean(row.isFinal) : Boolean(submission?.isFinal);
        const href = type === 'mine'
          ? isFinal ? `/mahasiswa/final-tasks/${row.finalTaskId}` : `/mahasiswa/meetings/${row.meetingId}/submission`
          : isFinal
            ? `/final-submissions/${submission?.id}/handler`
            : `/submissions/${row.courseSlug}/${row.meetingNumber}/handler?studentId=${encodeURIComponent(submission?.studentId ?? row.studentId ?? '')}`;
        const rawStatus = !submission ? 'Belum dikumpulkan' : submission.isCompleted ? 'Diterima' :
          [submission.aslabStatus, submission.laboranStatus, submission.dosenStatus].includes('Ditolak') ? 'Ditolak' :
          [submission.aslabStatus, submission.laboranStatus, submission.dosenStatus].includes('Revisi') ? 'Revisi' :
          `${submission.aslabStatus} / ${submission.laboranStatus}${isFinal ? ` / ${submission.dosenStatus}` : ''}`;
        return <tr key={submission?.id ?? `${row.studentId ?? row.taskId}-${index}`}>
          <td className="px-5 py-4"><p className="font-semibold text-slate-900">{type === 'mine' ? (isFinal && row.taskTitle === 'Laporan Final' ? t(locale, 'ui.finalReport') : localizedModuleTitle(locale, row.taskTitle, row.meetingNumber ?? 0)) : row.studentName ?? submission?.studentId ?? '—'}</p><p className="mt-1 text-xs text-slate-500">{type === 'mine' ? (isFinal ? t(locale, 'ui.finalTask') : `${t(locale, 'ui.module')} ${row.meetingNumber}`) : row.studentEmail ?? submission?.studentId ?? ''}</p></td>
          <td className="px-5 py-4"><p className="font-medium">{row.courseName ?? row.classGroup ?? (isFinal ? t(locale, 'ui.finalReport') : `${t(locale, 'ui.module')} ${row.meetingNumber ?? ''}`)}</p>{row.group && <p className="mt-1 text-xs text-slate-500">{t(locale, 'ui.class')} {row.group}</p>}{type === 'mine' && row.deadline && <p className="mt-1 text-xs text-slate-500">{t(locale, 'ui.deadlinePrefix')} {new Date(row.deadline).toLocaleString(locale === 'en' ? 'en-GB' : 'id-ID')}</p>}</td>
          <td className="px-5 py-4"><span className="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">{localizedStatus(locale, rawStatus)}</span></td>
          <td className="px-5 py-4">{type === 'mine' || submission?.id ? <Link href={href} className={secondary}>{type === 'mine' ? submission ? t(locale, 'ui.openStatus') : t(locale, 'ui.sendSubmission') : t(locale, 'ui.openReview')} <ArrowRight className="h-4 w-4" /></Link> : <span className="text-xs text-slate-400">{t(locale, 'ui.notSubmitted')}</span>}</td>
        </tr>;
      })}</tbody>
    </table></div>{!rows.length && <p className="px-5 py-12 text-center text-sm text-slate-500">{t(locale, 'ui.noData')}</p>}</div>
  </>;
}

function MeetingSubmissionsPage({ data, user }: { data: AnyRecord; user: SessionUser }) {
  const locale = useLocale();
  const router = useRouter();
  const course = data.course as AnyRecord;
  const meetings = data.meetings as AnyRecord[];
  const students = data.students as AnyRecord[];
  const publishedMeetings = meetings.filter((meeting) => Boolean(meeting.publishedAt));
  const moduleCountLabel = meetings.length === 1 && locale === 'en' ? 'module' : t(locale, 'submission.moduleCount');
  const isArchived = Boolean(course.isArchived || !course.semesterIsActive);
  const canManageModules = !isArchived && (user.role === 'Laboran' || (user.role === 'Aslab' && user.id === course.aslabId));
  const canReview = user.role === 'Aslab' || user.role === 'Laboran';
  const [query, setQuery] = useState('');
  const [incompleteOnly, setIncompleteOnly] = useState(false);
  const [activeStudentId, setActiveStudentId] = useState<string | null>(null);
  const [previewTarget, setPreviewTarget] = useState<{ previewUrl: string | null; driveUrl: string; title: string } | null>(null);
  const [deadline, setDeadline] = useState(data.meeting.deadline ? toLocalDateTime(data.meeting.deadline) : '');
  const [error, setError] = useState('');
  const updateDeadline = trpc.meetings.setDeadline.useMutation();
  const submissionByMeetingStudent = new Map<string, AnyRecord>();
  for (const meeting of meetings) {
    for (const submission of meeting.submissions as AnyRecord[]) {
      submissionByMeetingStudent.set(`${meeting.id}:${submission.studentId}`, submission);
    }
  }
  const getSubmission = (meetingId: number, studentId: string) => submissionByMeetingStudent.get(`${meetingId}:${studentId}`) ?? null;
  const submittedCount = (studentId: string) => publishedMeetings.reduce((count, meeting) => count + (getSubmission(meeting.id, studentId) ? 1 : 0), 0);
  const searchValue = query.trim().toLocaleLowerCase(locale === 'en' ? 'en-US' : 'id-ID');
  const filteredStudents = students.filter((student) => {
    const isComplete = publishedMeetings.length > 0 && submittedCount(student.id) === publishedMeetings.length;
    const matchesSearch = `${student.name} ${student.id}`.toLocaleLowerCase(locale === 'en' ? 'en-US' : 'id-ID').includes(searchValue);
    return matchesSearch && (!incompleteOnly || !isComplete);
  });
  const activeStudent = students.find((student) => student.id === activeStudentId) as AnyRecord | undefined;
  const dateLocale = locale === 'en' ? 'en-GB' : 'id-ID';
  const formatDate = (value: string | Date | null | undefined) => value ? new Intl.DateTimeFormat(dateLocale, {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta',
  }).format(new Date(value)) : '';
  const formatScore = (value: unknown) => value === null || value === undefined ? '—' : new Intl.NumberFormat(dateLocale, {
    minimumFractionDigits: 2, maximumFractionDigits: 2,
  }).format(Number(value));
  const scoreLetter = (score: number | null) => score === null ? null : score >= 86 ? 'A' : score >= 75 ? 'B' : score >= 60 ? 'C' : score >= 40 ? 'D' : 'E';
  const reviewStatusText = (value: string) => {
    if (locale !== 'en') return value;
    return ({ Pending: 'Pending', Revisi: 'Revision requested', Ditolak: 'Rejected', ACC: 'Approved', 'N/A': 'Not applicable' } as Record<string, string>)[value] ?? value;
  };
  const statusStyle = (value: string) => {
    switch (value.toUpperCase()) {
      case 'ACC': return 'border-emerald-400 bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200';
      case 'REVISI': return 'border-amber-300 bg-amber-50 text-amber-800';
      case 'DITOLAK': return 'border-rose-300 bg-rose-50 text-rose-800';
      default: return 'border-slate-200 bg-white text-slate-600';
    }
  };
  const drivePreviewUrl = (driveUrl: string) => {
    try {
      const url = new URL(driveUrl);
      if (url.protocol !== 'https:' || url.hostname !== 'drive.google.com') return null;
      const match = url.pathname.match(/^\/file\/d\/([A-Za-z0-9_-]+)(?:\/(?:view|preview|edit))?\/?$/);
      const fileId = match?.[1] ?? (['/open', '/uc'].includes(url.pathname) ? url.searchParams.get('id') : null);
      if (!fileId || !/^[A-Za-z0-9_-]+$/.test(fileId)) return null;
      const preview = new URL(`/file/d/${fileId}/preview`, 'https://drive.google.com');
      const resourceKey = url.searchParams.get('resourcekey');
      if (resourceKey && /^[A-Za-z0-9_-]+$/.test(resourceKey)) preview.searchParams.set('resourcekey', resourceKey);
      return preview.toString();
    } catch {
      return null;
    }
  };

  useEffect(() => {
    if (!activeStudentId && !previewTarget) return;
    const closeOnEscape = (event: KeyboardEvent) => {
      if (event.key !== 'Escape') return;
      if (previewTarget) setPreviewTarget(null);
      else setActiveStudentId(null);
    };
    window.addEventListener('keydown', closeOnEscape);
    return () => window.removeEventListener('keydown', closeOnEscape);
  }, [activeStudentId, previewTarget]);

  function saveDeadline(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError('');
    updateDeadline.mutate({ id: data.meeting.id, deadline: new Date(deadline) }, {
      onSuccess: () => {
        void showAppAlert('success', t(locale, 'submission.deadlineSavedTitle'), t(locale, 'submission.deadlineSavedText'), locale);
        router.refresh();
      },
      onError: (reason) => setError(localizeServerMessage(locale, reason.message)),
    });
  }

  return <div className="mx-auto max-w-[95rem] space-y-6 px-1 sm:px-0">
    {isArchived && <p className="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">{t(locale, 'submission.archived')} — {locale === 'en' ? 'This class is read-only.' : 'Kelas arsip hanya dapat dibaca.'}</p>}
    <div className="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
      <div className="min-w-0">
        <Link href={`/courses/${course.slug}`} className="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800"><ArrowLeft className="h-4 w-4" /> {t(locale, 'submission.backToClass')}</Link>
        <p className="text-xs font-bold uppercase tracking-[0.15em] text-emerald-800">{appName}</p>
        <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{course.courseName} · {course.classGroup}</h1>
        <p className="mt-2 text-sm text-slate-500">{t(locale, 'submission.studentListSummary')} · {meetings.length} {moduleCountLabel}</p>
      </div>
      <div className="flex flex-col gap-2 sm:items-end">
        {canManageModules && <Link href={`/courses/${course.slug}/modules/edit`} className={secondary}>{t(locale, 'submission.manageModules')}</Link>}
        {!isArchived && <form onSubmit={saveDeadline} className="flex flex-wrap items-end gap-2 rounded-xl border border-slate-200 bg-white p-2">
          <label className="block"><span className="sr-only">{t(locale, 'submission.deadline')} · {localizedModuleTitle(locale, data.meeting.title, data.meeting.meetingNumber)}</span><input type="datetime-local" value={deadline} onChange={(event) => setDeadline(event.target.value)} required className="rounded-lg border-slate-200 text-xs" /></label>
          <button className="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white" disabled={updateDeadline.isPending}>{t(locale, 'submission.saveDeadline')}</button>
        </form>}
        <Notice error>{error}</Notice>
      </div>
    </div>

    {data.meeting.description && <section className={`${card} p-5`}>
      <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{localizedModuleTitle(locale, data.meeting.title, data.meeting.meetingNumber)} · {t(locale, 'submission.instructions')}</p>
      <p className="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{data.meeting.description}</p>
    </section>}

    <section className="grid gap-3 sm:grid-cols-3">
      <article className={`${card} p-5`}><p className="text-xs font-semibold text-slate-500">{t(locale, 'submission.students')}</p><p className="mt-1 text-2xl font-bold text-slate-900">{students.length}</p></article>
      <article className={`${card} p-5`}><p className="text-xs font-semibold text-slate-500">{t(locale, 'submission.modulesOpen')}</p><p className="mt-1 text-2xl font-bold text-slate-900">{publishedMeetings.length}<span className="text-base font-medium text-slate-400"> / {meetings.length}</span></p></article>
      <article className={`${card} p-5`}><p className="text-xs font-semibold text-slate-500">{t(locale, 'submission.reportsReceived')}</p><p className="mt-1 text-2xl font-bold text-emerald-700">{publishedMeetings.reduce((count, meeting) => count + (meeting.submissions as AnyRecord[]).length, 0)}</p></article>
    </section>

    <section className={`${card} overflow-hidden`}>
      <div className="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <label className="relative block w-full sm:max-w-md"><span className="sr-only">{t(locale, 'submission.search')}</span><Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" /><input type="search" value={query} onChange={(event) => setQuery(event.target.value)} placeholder={t(locale, 'submission.search')} className={`${inputClass} pl-10`} /></label>
        <label className="flex items-center gap-2 text-sm font-medium text-slate-600"><input type="checkbox" checked={incompleteOnly} onChange={(event) => setIncompleteOnly(event.target.checked)} className="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />{t(locale, 'submission.incompleteOnly')}</label>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[1200px] text-left text-sm">
          <thead className="border-b border-slate-100 bg-white text-xs font-semibold uppercase tracking-wide text-slate-500"><tr>
            <th className="px-6 py-4">{t(locale, 'submission.student')}</th><th className="px-5 py-4 text-center">{t(locale, 'submission.submittedCount')}</th>
            {meetings.map((module) => <th key={module.id} className="min-w-36 px-3 py-4 text-center">{t(locale, 'submission.moduleStatus')} {module.meetingNumber}</th>)}
            <th className="px-6 py-4 text-center">{t(locale, 'submission.actions')}</th>
          </tr></thead>
          <tbody className="divide-y divide-slate-100">
            {filteredStudents.map((student) => {
              const studentSubmitted = submittedCount(student.id);
              const complete = publishedMeetings.length > 0 && studentSubmitted === publishedMeetings.length;
              return <tr key={student.id} className="hover:bg-slate-50/70">
                <td className="px-6 py-5"><p className="font-semibold text-slate-900">{student.name}</p><p className="mt-0.5 text-xs text-slate-500">{student.id}</p></td>
                <td className="px-3 py-4 text-center align-middle"><div className={`mx-auto flex min-h-[4.5rem] w-20 flex-col items-center justify-center rounded-lg px-2 py-2 ${complete ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800'}`}><span className="text-base font-bold leading-5">{studentSubmitted}/{publishedMeetings.length}</span><span className="mt-1 text-xs font-medium leading-4">{t(locale, 'submission.moduleCount')}</span></div></td>
                {meetings.map((module) => {
                  const submission = getSubmission(module.id, student.id);
                  return <td key={module.id} className="px-3 py-5 text-center">
                    {!module.publishedAt ? <span className="text-xs text-slate-400">{t(locale, 'submission.notOpened')}</span> : submission ? <div className="flex flex-col items-center gap-1.5 text-xs">
                      {([['Aslab', submission.aslabStatus], ['Laboran', submission.laboranStatus]] as const).map(([reviewer, status]) => <span key={reviewer} className={`rounded-full border px-2.5 py-1 ${statusStyle(String(status))}`}>{t(locale, reviewer === 'Aslab' ? 'submission.aslab' : 'submission.laboran')}: {reviewStatusText(String(status))}</span>)}
                    </div> : <span className="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-500">{t(locale, 'submission.notSubmitted')}</span>}
                  </td>;
                })}
                <td className="px-6 py-5 text-center"><button type="button" onClick={() => setActiveStudentId(student.id)} className="rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800">{t(locale, 'submission.detail')}</button></td>
              </tr>;
            })}
            {!filteredStudents.length && <tr><td colSpan={meetings.length + 3} className="px-6 py-12 text-center text-sm text-slate-500">{query || incompleteOnly ? t(locale, 'submission.noSearchResults') : t(locale, 'submission.noStudents')}</td></tr>}
          </tbody>
        </table>
      </div>
    </section>

    {activeStudent && <div className="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6" role="dialog" aria-modal="true" onClick={(event) => { if (event.target === event.currentTarget) setActiveStudentId(null); }}>
      <section className="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <header className="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
          <div><p className="text-xs font-semibold uppercase tracking-wide text-emerald-700">{t(locale, 'submission.detailTitle')}</p><h2 className="mt-1 text-lg font-bold text-slate-900">{activeStudent.name}</h2><p className="text-sm text-slate-500">{t(locale, 'submission.nim')} {activeStudent.id} · {meetings.length} {moduleCountLabel}</p></div>
          <button type="button" onClick={() => setActiveStudentId(null)} className={secondary}>{t(locale, 'submission.close')}</button>
        </header>
        <div className="min-h-0 space-y-3 overflow-y-auto p-4 sm:p-6">
          {meetings.map((module) => {
            const submission = getSubmission(module.id, activeStudent.id);
            const aslabScore = submission?.aslabScore === null || submission?.aslabScore === undefined ? null : Number(submission.aslabScore);
            const laboranScore = submission?.laboranScore === null || submission?.laboranScore === undefined ? null : Number(submission.laboranScore);
            const moduleScore = aslabScore !== null && laboranScore !== null ? Math.round((aslabScore * 0.8 + laboranScore * 0.2) * 100) / 100 : null;
            const moduleGrade = scoreLetter(moduleScore);
            const reviewerHasApproved = submission && ((user.role === 'Aslab' && submission.aslabStatus === 'ACC') || (user.role === 'Laboran' && submission.laboranStatus === 'ACC'));
            const laboranWaitingAslab = submission && user.role === 'Laboran' && submission.aslabStatus !== 'ACC';
            return <article key={module.id} className="rounded-xl border border-slate-200 p-4">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div className="min-w-0">
                  <h3 className="font-semibold text-slate-900">{locale === 'en' ? 'Module' : 'Modul'} {module.meetingNumber}</h3>
                  {!module.publishedAt ? <p className="mt-1 text-xs font-medium text-slate-400">{t(locale, 'submission.moduleNotOpen')}</p>
                    : !submission ? <p className="mt-1 text-xs font-medium text-amber-700">{t(locale, 'submission.notSubmitted')} · {t(locale, 'submission.deadlinePrefix')} {module.deadline ? formatDate(module.deadline) : locale === 'en' ? 'unlimited' : 'tidak dibatasi'}</p>
                      : <>
                        <p className="mt-1 text-xs text-slate-500">{t(locale, 'submission.submittedAt')} {formatDate(submission.lastUploadAt)} WIB · {submission.historiesCount} {t(locale, 'submission.histories')}</p>
                        <div className="mt-3 flex flex-wrap gap-2 text-xs"><span className={`rounded-full border px-2.5 py-1 ${statusStyle(submission.aslabStatus)}`}>{t(locale, 'submission.aslab')}: {reviewStatusText(submission.aslabStatus)}</span><span className={`rounded-full border px-2.5 py-1 ${statusStyle(submission.laboranStatus)}`}>{t(locale, 'submission.laboran')}: {reviewStatusText(submission.laboranStatus)}</span></div>
                        <dl className="mt-3 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                          <div className="rounded-lg bg-slate-50 px-3 py-2"><dt className="text-slate-500">{t(locale, 'submission.scoreAslab')}</dt><dd className="mt-1 font-semibold text-slate-900">{formatScore(aslabScore)}</dd></div>
                          <div className="rounded-lg bg-slate-50 px-3 py-2"><dt className="text-slate-500">{t(locale, 'submission.scoreLaboran')}</dt><dd className="mt-1 font-semibold text-slate-900">{formatScore(laboranScore)}</dd></div>
                          <div className="rounded-lg bg-emerald-50 px-3 py-2"><dt className="text-emerald-700">{t(locale, 'submission.moduleScore')}</dt><dd className="mt-1 font-semibold text-emerald-800">{formatScore(moduleScore)}</dd></div>
                          <div className="rounded-lg bg-emerald-50 px-3 py-2"><dt className="text-emerald-700">{t(locale, 'submission.letterGrade')}</dt><dd className="mt-1 font-semibold text-emerald-800">{moduleGrade ?? '—'}</dd></div>
                        </dl>
                      </>}
                </div>
                {submission && <div className="flex shrink-0 flex-wrap gap-2">
                  <button type="button" onClick={() => {
                    const documentUrl = submission.filePath ? `/submissions/${submission.id}/file` : submission.submissionLink ?? '';
                    setPreviewTarget({ previewUrl: submission.filePath ? documentUrl : drivePreviewUrl(documentUrl), driveUrl: documentUrl, title: `${activeStudent.name} · ${locale === 'en' ? 'Module' : 'Modul'} ${module.meetingNumber}` });
                  }} className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">{t(locale, 'submission.preview')}</button>
                  {(submission.submissionLink || submission.filePath) && <a href={submission.filePath ? `/submissions/${submission.id}/file` : submission.submissionLink} target="_blank" rel="noopener noreferrer" className="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">{t(locale, 'submission.openDrive')}</a>}
                  {reviewerHasApproved ? <button type="button" disabled className="cursor-not-allowed rounded-lg border border-emerald-400 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200">{t(locale, 'submission.alreadyApproved')}</button>
                    : isArchived ? <button type="button" disabled className="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-400">{t(locale, 'submission.archived')}</button>
                      : laboranWaitingAslab ? <button type="button" disabled className="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500">{t(locale, 'submission.waitingAslab')}</button>
                        : canReview && <Link href={`/submissions/${course.slug}/${module.meetingNumber}/handler?studentId=${encodeURIComponent(activeStudent.id)}`} className="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white">{t(locale, 'submission.review')}</Link>}
                </div>}
              </div>
            </article>;
          })}
        </div>
      </section>
    </div>}

    {previewTarget && <div className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/75 p-3 sm:p-6" role="dialog" aria-modal="true" onClick={(event) => { if (event.target === event.currentTarget) setPreviewTarget(null); }}>
      <section className="flex h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <header className="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-6"><div className="min-w-0"><h2 className="truncate font-semibold text-slate-900">{t(locale, 'submission.previewHeading')} · {previewTarget.title}</h2><p className="mt-1 text-xs text-slate-500">{t(locale, 'submission.previewHelp')}</p></div><div className="flex shrink-0 gap-2">{previewTarget.driveUrl && <a href={previewTarget.driveUrl} target="_blank" rel="noopener noreferrer" className="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">{t(locale, 'submission.openDrive')}</a>}<button type="button" onClick={() => setPreviewTarget(null)} className="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">{t(locale, 'submission.close')}</button></div></header>
        {previewTarget.previewUrl ? <iframe src={previewTarget.previewUrl} title={previewTarget.title} className="min-h-0 w-full flex-1 bg-slate-50" referrerPolicy="no-referrer" /> : <p className="flex flex-1 items-center justify-center p-8 text-center text-sm text-slate-500">{t(locale, 'submission.previewUnavailable')}</p>}
      </section>
    </div>}
  </div>;
}

function SemesterManager({ rows }: { rows: AnyRecord[] }) {
  const locale = useLocale();
  const create = trpc.semesters.create.useMutation(); const update = trpc.semesters.update.useMutation(); const activate = trpc.semesters.setActive.useMutation(); const remove = trpc.semesters.delete.useMutation();
  const [name, setName] = useState(''); const [error, setError] = useState(''); const router = useRouter();
  const done = (fn: () => void) => ({ onSuccess: () => { setError(''); fn(); router.refresh(); }, onError: (reason: { message: string }) => setError(localizeServerMessage(locale, reason.message)) });
  return <><Heading title={t(locale, 'ui.semesterManagement')} description={t(locale, 'ui.semesterManagementDescription')} /><form onSubmit={(event) => { event.preventDefault(); create.mutate({ name }, { onSuccess: () => { setName(''); router.refresh(); }, onError: (reason) => setError(localizeServerMessage(locale, reason.message)) }); }} className={`${card} mb-5 flex flex-col gap-3 p-4 sm:flex-row`}><input className={inputClass} value={name} onChange={(event) => setName(event.target.value)} placeholder={t(locale, 'ui.semesterExample')} required /><button className={primary}><Plus className="h-4 w-4" /> {t(locale, 'ui.addSemester')}</button></form><Notice error>{error}</Notice><div className={`${card} divide-y divide-slate-100`}>{rows.map((semester) => <div key={semester.id} className="flex flex-wrap items-center justify-between gap-3 p-4"><div><p className="font-semibold text-slate-900">{semester.name}</p><p className="mt-1 text-xs text-slate-500">{semester.isActive ? t(locale, 'ui.semesterActive') : t(locale, 'ui.inactive')}</p></div><div className="flex gap-2">{!semester.isActive && <button onClick={() => activate.mutate({ id: semester.id }, done(() => {}))} className={secondary}>{t(locale, 'ui.setActive')}</button>}<button onClick={() => { void promptAppText(t(locale, 'ui.semester'), semester.name, locale).then((next) => { if (next) update.mutate({ id: semester.id, name: next }, done(() => {})); }); }} className={secondary}>{t(locale, 'ui.edit')}</button><button onClick={() => { void confirmAppAction(t(locale, 'ui.deleteSemesterConfirm'), t(locale, 'ui.deleteSemesterText'), 'warning', locale).then((confirmed) => { if (confirmed) remove.mutate({ id: semester.id }, done(() => {})); }); }} className="rounded-xl px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">{t(locale, 'ui.delete')}</button></div></div>)}{!rows.length && <p className="p-8 text-center text-sm text-slate-500">{t(locale, 'ui.noSemesters')}</p>}</div></>;
}

function ApprovalManager({ data }: { data: AnyRecord }) {
  const locale = useLocale();
  const approve = trpc.users.approve.useMutation(); const approveAll = trpc.users.approveAll.useMutation(); const remove = trpc.users.deletePending.useMutation(); const router = useRouter(); const [error, setError] = useState('');
  const ok = { onSuccess: () => router.refresh(), onError: (reason: { message: string }) => setError(localizeServerMessage(locale, reason.message)) };
  return <><Heading title={t(locale, 'ui.accountApprovals')} description={copy(locale, 'ui.accountsPending', { count: data.total })} actions={<button className={primary} onClick={() => approveAll.mutate(undefined, ok)} disabled={!data.total}>{t(locale, 'ui.approveAll')}</button>} /><Notice error>{error}</Notice><div className={`${card} divide-y divide-slate-100`}>{data.users.map((user: AnyRecord) => <div key={user.id} className="flex flex-wrap items-center justify-between gap-4 p-4 sm:p-5"><div><p className="font-semibold text-slate-900">{user.name}</p><p className="mt-1 text-xs text-slate-500">{user.id} · {user.email}</p><p className="mt-1 text-xs text-slate-400">{t(locale, 'ui.registeredOn')} {new Date(user.createdAt).toLocaleDateString(locale === 'en' ? 'en-GB' : 'id-ID')}</p></div><div className="flex gap-2"><button onClick={() => approve.mutate({ id: user.id }, ok)} className={primary}>{t(locale, 'ui.approve')}</button><button onClick={() => { void confirmAppAction(t(locale, 'ui.deleteAccountConfirm'), locale === 'en' ? `The account for ${user.name} will be deleted.` : `Akun ${user.name} akan dihapus.`, 'warning', locale).then((confirmed) => { if (confirmed) remove.mutate({ id: user.id }, ok); }); }} className="rounded-xl px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">{t(locale, 'ui.delete')}</button></div></div>)}{!data.users.length && <p className="p-10 text-center text-sm text-slate-500">{t(locale, 'ui.pendingAccountsProcessed')}</p>}</div></>;
}

function TutorialManager({ rows, isLaboran }: { rows: AnyRecord[]; isLaboran: boolean }) {
  const locale = useLocale();
  const create = trpc.tutorials.create.useMutation(); const remove = trpc.tutorials.delete.useMutation(); const router = useRouter(); const [error, setError] = useState('');
  function submit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); const form = new FormData(event.currentTarget); create.mutate({ title: String(form.get('title')), description: String(form.get('description')) || null, type: String(form.get('type')) as 'youtube' | 'gdrive_pdf', url: String(form.get('url')) }, { onSuccess: () => { (event.currentTarget as HTMLFormElement).reset(); router.refresh(); }, onError: (reason) => setError(localizeServerMessage(locale, reason.message)) }); }
  return <><Heading title={t(locale, 'ui.tutorials')} description={t(locale, 'ui.tutorialDescription')} />{isLaboran && <form onSubmit={submit} className={`${card} mb-6 grid gap-3 p-5 sm:grid-cols-2`}><input name="title" className={inputClass} placeholder={t(locale, 'ui.tutorialTitle')} required /><select name="type" className={inputClass}><option value="youtube">YouTube</option><option value="gdrive_pdf">{t(locale, 'ui.youtubePdf')}</option></select><input name="url" type="url" className={`${inputClass} sm:col-span-2`} placeholder={t(locale, 'ui.materialUrl')} required /><textarea name="description" className={`${inputClass} sm:col-span-2`} rows={3} placeholder={t(locale, 'ui.descriptionOptional')} /><Notice error>{error}</Notice><button className={primary}><Plus className="h-4 w-4" /> {t(locale, 'ui.addTutorial')}</button></form>}<div className="grid gap-4 lg:grid-cols-2">{rows.map((row) => <article className={`${card} overflow-hidden`} key={row.id}><div className="aspect-video bg-slate-100">{row.type === 'youtube' ? <iframe title={row.title} src={youtubeEmbed(row.url)} className="h-full w-full" allowFullScreen /> : <iframe title={row.title} src={row.url.replace('/view', '/preview')} className="h-full w-full" />}</div><div className="p-5"><p className="text-xs font-semibold uppercase tracking-wider text-emerald-700">{row.type === 'youtube' ? t(locale, 'ui.video') : t(locale, 'ui.document')}</p><h2 className="mt-2 text-lg font-bold">{row.title}</h2>{row.description && <p className="mt-2 text-sm leading-6 text-slate-600">{row.description}</p>}{isLaboran && <button onClick={() => { void confirmAppAction(t(locale, 'ui.deleteTutorialConfirm'), t(locale, 'ui.deleteTutorialText'), 'warning', locale).then((confirmed) => { if (confirmed) remove.mutate({ id: row.id }, { onSuccess: () => router.refresh() }); }); }} className="mt-4 text-sm font-semibold text-rose-700">{t(locale, 'ui.delete')}</button>}</div></article>)}</div>{!rows.length && <Empty message={t(locale, 'ui.noTutorials')} />}</>;
}

function youtubeEmbed(value: string) { try { const url = new URL(value); const id = url.hostname === 'youtu.be' ? url.pathname.slice(1) : url.searchParams.get('v') ?? url.pathname.split('/').filter(Boolean).at(-1); return id ? `https://www.youtube-nocookie.com/embed/${id}` : value; } catch { return value; } }

function CourseGrades({ rows, slug, course, user }: { rows: AnyRecord[]; slug: string; course: AnyRecord; user: SessionUser }) {
  const locale = useLocale();
  const mutation = trpc.courses.updateGrade.useMutation(); const router = useRouter();
  const archived = Boolean(course.isArchived || !course.semesterIsActive);
  const canEdit = user.role === 'Dosen' && !archived;
  const [exporting, setExporting] = useState(false);
  async function exportGrades() {
    setExporting(true);
    try {
      const { downloadGradeWorkbook } = await import('@/lib/grade-export');
      await downloadGradeWorkbook(rows as GradeExportRow[], course as GradeExportCourse, locale);
      await showAppAlert('success', t(locale, 'ui.gradesExported'), t(locale, 'ui.gradesExportComplete'), locale);
    } catch {
      await showAppAlert('error', t(locale, 'ui.gradesExportError'), t(locale, 'ui.gradesExportError'), locale);
    } finally {
      setExporting(false);
    }
  }
  const formatScore = (value: number | null | undefined) => value === null || value === undefined
    ? '—'
    : new Intl.NumberFormat(locale === 'en' ? 'en-US' : 'id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
  return <div className="mx-auto max-w-[95rem] space-y-6 px-0 sm:px-2">
    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><Link href="/dashboard" className="mb-3 inline-flex text-sm font-semibold text-emerald-700">← {t(locale, 'ui.backToDashboard')}</Link><h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{t(locale, 'ui.courseGrades')}</h1><p className="mt-1 text-sm text-slate-500">{course.courseName} · {t(locale, 'ui.class')} {course.classGroup} · {localizedSemesterName(locale, course.semesterName)}</p></div>
      <div className="flex flex-wrap gap-2"><button type="button" onClick={() => void exportGrades()} disabled={exporting} className={secondary}><Download className="h-4 w-4" />{exporting ? t(locale, 'ui.exportingGrades') : t(locale, 'ui.exportGradesExcel')}</button><Link href={`/courses/${slug}`} className={secondary}>{t(locale, 'ui.openCourse')}</Link></div>
    </div>
    {archived && <p className="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">{t(locale, 'ui.archivedGradesReadOnly')}</p>}
    {!canEdit && !archived && <p className="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">{t(locale, 'ui.gradesReadOnlyForStaff')}</p>}
    <section className="rounded-2xl border border-sky-100 bg-sky-50/70 p-5 text-sm leading-6 text-sky-950"><p className="font-semibold">{t(locale, 'ui.gradeCalculationTitle')}</p><p className="mt-1">{t(locale, 'ui.courseGradesDescription')}</p><p className="mt-1 text-xs text-sky-800">{t(locale, 'ui.gradeScaleDescription')}</p><p className="mt-2 border-t border-sky-100 pt-2 text-xs text-sky-800">{t(locale, 'ui.examGradeEntryNote')}</p></section>
    <div className={`${card} overflow-x-auto`}><table className="w-full min-w-[1050px] text-left text-sm">
      <thead className="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500"><tr><th className="px-5 py-4">{t(locale, 'ui.students')}</th><th className="px-4 py-4 text-center">{t(locale, 'ui.reportGrade')}</th><th className="px-4 py-4 text-center">{t(locale, 'ui.midterm')}</th><th className="px-4 py-4 text-center">{t(locale, 'ui.finalExam')}</th><th className="px-4 py-4 text-center">{t(locale, 'ui.finalGrade')}</th><th className="px-5 py-4">{canEdit ? t(locale, 'ui.saveLecturerGrade') : t(locale, 'ui.readOnlyGrades')}</th></tr></thead>
      <tbody className="divide-y divide-slate-100">{rows.map((row) => <GradeRow key={row.id} row={row} slug={slug} canEdit={canEdit} moduleCount={course.meetings?.length ?? row.modules.length} mutation={mutation} formatScore={formatScore} onSaved={() => router.refresh()} />)}
        {!rows.length && <tr><td colSpan={6} className="px-6 py-14 text-center text-sm text-slate-500">{t(locale, 'ui.noParticipants')}</td></tr>}
      </tbody>
    </table></div>
  </div>;
}

function GradeRow({ row, slug, canEdit, moduleCount, mutation, formatScore, onSaved }: { row: AnyRecord; slug: string; canEdit: boolean; moduleCount: number; mutation: ReturnType<typeof trpc.courses.updateGrade.useMutation>; formatScore: (value: number | null | undefined) => string; onSaved: () => void }) {
  const locale = useLocale();
  const [uts, setUts] = useState(row.uts?.toString() ?? '');
  const [uas, setUas] = useState(row.uas?.toString() ?? '');
  const readyModules = row.modules.filter((module: AnyRecord) => module.isCompleted && module.hasAslabScore && module.hasLaboranScore).length;
  const numericInputs = uts.trim() !== '' && uas.trim() !== '' && Number.isFinite(Number(uts)) && Number.isFinite(Number(uas));
  const status = (value: number | null | undefined, letter: string | null | undefined) => value === null || value === undefined ? '—' : <>{formatScore(value)}{letter && <span className="ml-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">{letter}</span>}</>;
  return <tr className="align-top hover:bg-slate-50/50">
    <td className="px-5 py-5"><p className="font-semibold text-slate-900">{row.name}</p><p className="mt-1 text-xs text-slate-500">{row.id}</p><details className="mt-3"><summary className="cursor-pointer text-xs font-semibold text-emerald-700">{copy(locale, 'ui.moduleGradeDetails', { ready: readyModules, total: moduleCount })}</summary><div className="mt-3 min-w-[330px] space-y-2">{row.modules.map((module: AnyRecord) => <div key={module.meetingNumber} className="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-xs"><span className="min-w-0 truncate text-slate-700">{localizedModuleTitle(locale, module.meetingTitle, module.meetingNumber)}</span>{module.hasSubmission ? <span className="shrink-0 text-right text-slate-600">{t(locale, 'ui.labAssistantShort')} {formatScore(module.aslabScore)} · {t(locale, 'ui.labAdministratorShort')} {formatScore(module.laboranScore)}<br /><strong className="text-emerald-700">{t(locale, 'ui.moduleGrades')} {status(module.score, null)}</strong></span> : <span className="shrink-0 text-amber-700">{t(locale, 'ui.notSubmittedYet')}</span>}</div>)}</div></details></td>
    <td className="px-4 py-5 text-center">{row.laprak !== null ? <span className="font-bold text-slate-900">{status(row.laprak, row.laprakLetter)}</span> : <span className="text-xs text-amber-700">{t(locale, 'ui.reportIncomplete')}<br />{readyModules}/{moduleCount} {t(locale, 'ui.modulesGraded')}</span>}</td>
    <td className="px-4 py-5 text-center">{canEdit ? <input type="number" min="0" max="100" step="0.01" value={uts} onChange={(event) => setUts(event.target.value)} className="w-24 rounded-lg border border-slate-200 px-3 py-2" aria-label={`${t(locale, 'ui.midterm')} ${row.name}`} /> : status(row.uts, row.utsLetter)}</td>
    <td className="px-4 py-5 text-center">{canEdit ? <input type="number" min="0" max="100" step="0.01" value={uas} onChange={(event) => setUas(event.target.value)} className="w-24 rounded-lg border border-slate-200 px-3 py-2" aria-label={`${t(locale, 'ui.finalExam')} ${row.name}`} /> : status(row.uas, row.uasLetter)}</td>
    <td className="px-4 py-5 text-center">{row.final !== null ? <span className="text-lg font-bold text-emerald-800">{status(row.final, row.finalLetter)}</span> : <span className="text-xs text-slate-400">{t(locale, 'ui.waitingForCompleteGrades')}</span>}</td>
    <td className="px-5 py-5">{canEdit ? <button className={primary} disabled={!numericInputs || mutation.isPending} onClick={() => mutation.mutate({ slug, studentId: row.id, utsScore: Number(uts), uasScore: Number(uas) }, { onSuccess: () => { void showAppAlert('success', t(locale, 'ui.actionSucceeded'), t(locale, 'ui.gradesSaved'), locale); onSaved(); }, onError: (reason) => { void showAppAlert('error', t(locale, 'ui.couldNotProcess'), localizeServerMessage(locale, reason.message), locale); } })}>{t(locale, 'ui.save')}</button> : <span className="text-xs text-slate-500">{t(locale, 'ui.readOnlyGrades')}</span>}</td>
  </tr>;
}
function ProfilePanel({ user }: { user: SessionUser }) {
  const locale = useLocale();
  const update = trpc.auth.updateProfile.useMutation(); const password = trpc.auth.updatePassword.useMutation(); const [error, setError] = useState(''); const [message, setMessage] = useState(''); const router = useRouter();
  return <><Heading title={t(locale, 'ui.myProfile')} description={t(locale, 'ui.profileDescription')} /><div className="grid gap-5 lg:grid-cols-2"><form className={`${card} space-y-4 p-5 sm:p-7`} onSubmit={(event) => { event.preventDefault(); const form = new FormData(event.currentTarget); update.mutate({ name: String(form.get('name')), email: String(form.get('email')) }, { onSuccess: () => { setMessage(t(locale, 'ui.profileUpdated')); setError(''); router.refresh(); }, onError: (reason) => setError(localizeServerMessage(locale, reason.message)) }); }}><h2 className="text-lg font-bold">{t(locale, 'ui.profileInformation')}</h2><Notice error>{error}</Notice><Notice>{message}</Notice><label className="block"><span className="mb-2 block text-xs font-semibold">{t(locale, 'ui.name')}</span><input name="name" defaultValue={user.name} className={inputClass} required /></label><label className="block"><span className="mb-2 block text-xs font-semibold">{t(locale, 'auth.email')}</span><input name="email" type="email" defaultValue={user.email} className={inputClass} required /></label><p className="text-xs text-slate-500">{t(locale, 'ui.id')}: {user.id} · {t(locale, 'ui.role')}: {t(locale, `role.${user.role}` as Parameters<typeof t>[1])}</p><button className={primary}>{t(locale, 'ui.saveChanges')}</button></form><form className={`${card} space-y-4 p-5 sm:p-7`} onSubmit={(event) => { event.preventDefault(); const form = new FormData(event.currentTarget); password.mutate({ currentPassword: String(form.get('currentPassword')), password: String(form.get('password')), passwordConfirmation: String(form.get('passwordConfirmation')) }, { onSuccess: () => { setMessage(t(locale, 'ui.passwordUpdated')); (event.currentTarget as HTMLFormElement).reset(); }, onError: (reason) => setError(localizeServerMessage(locale, reason.message)) }); }}><h2 className="text-lg font-bold">{t(locale, 'ui.changePassword')}</h2><input name="currentPassword" type="password" className={inputClass} placeholder={t(locale, 'ui.passwordCurrent')} required /><input name="password" type="password" className={inputClass} placeholder={t(locale, 'ui.passwordNew')} minLength={8} required /><input name="passwordConfirmation" type="password" className={inputClass} placeholder={t(locale, 'ui.passwordConfirm')} minLength={8} required /><button className={primary}>{t(locale, 'ui.updatePassword')}</button></form></div></>;
}

function ProfileExtras() {
  const locale = useLocale();
  const [avatar, setAvatar] = useState<File | null>(null);
  const [avatarError, setAvatarError] = useState('');
  const [avatarMessage, setAvatarMessage] = useState('');
  const [deleteError, setDeleteError] = useState('');
  const [deleteMessage, setDeleteMessage] = useState('');
  const router = useRouter();
  const deletion = trpc.auth.deleteAccount.useMutation();

  async function uploadAvatar(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!avatar) return;
    const form = new FormData();
    form.set('avatar', avatar);
    setAvatarError('');
    setAvatarMessage('');
    try {
      const response = await fetch('/api/avatar', { method: 'POST', body: form });
      const result = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(result.error ?? (locale === 'en' ? 'The profile photo could not be saved.' : 'Foto profil gagal disimpan.'));
      const successText = t(locale, 'ui.photoUpdatedMessage');
      setAvatarMessage(successText);
      setAvatar(null);
      await showAppAlert('success', locale === 'en' ? 'Photo updated' : 'Foto diperbarui', successText, locale);
      router.refresh();
    } catch (reason) {
      const errorText = reason instanceof Error ? localizeServerMessage(locale, reason.message) : (locale === 'en' ? 'The profile photo could not be saved.' : 'Foto profil gagal disimpan.');
      setAvatarError(errorText);
      await showAppAlert('error', locale === 'en' ? 'Could not update photo' : 'Foto tidak dapat diperbarui', errorText, locale);
    }
  }

  return <div className="mt-5 grid gap-5 lg:grid-cols-2">
    <form className={`${card} space-y-4 p-5 sm:p-7`} onSubmit={uploadAvatar}>
      <h2 className="text-lg font-bold">{t(locale, 'ui.profilePhoto')}</h2>
      <p className="text-sm text-slate-500">{t(locale, 'ui.photoRequirements')}</p>
      <input type="file" accept="image/png,image/jpeg,image/webp" onChange={(event) => setAvatar(event.target.files?.[0] ?? null)} className={inputClass} required />
      <Notice error>{avatarError}</Notice><Notice>{avatarMessage}</Notice>
      <button className={primary} disabled={!avatar}>{t(locale, 'ui.saveProfilePhoto')}</button>
    </form>
    <form className={`${card} space-y-4 border-rose-200 p-5 sm:p-7`} onSubmit={(event) => {
      event.preventDefault();
      const form = new FormData(event.currentTarget);
      void confirmAppAction(t(locale, 'ui.deleteAccountConfirm'), t(locale, 'ui.deleteAccountWarning'), 'warning', locale).then((confirmed) => {
        if (!confirmed) return;
        deletion.mutate({ password: String(form.get('password')) }, {
          onError: (reason) => { setDeleteError(localizeServerMessage(locale, reason.message)); void showAppAlert('error', t(locale, 'ui.couldNotProcess'), localizeServerMessage(locale, reason.message), locale); },
          onSuccess: async () => {
            const successText = t(locale, 'ui.accountDeletedMessage');
            setDeleteMessage(successText);
            await showAppAlert('success', locale === 'en' ? 'Account deleted' : 'Akun dihapus', successText, locale);
            router.replace('/');
            router.refresh();
          },
        });
      });
    }}>
      <h2 className="text-lg font-bold text-rose-800">{t(locale, 'ui.deleteAccount')}</h2>
      <p className="text-sm text-slate-500">{t(locale, 'ui.deleteAccountBlocked')}</p>
      <input name="password" type="password" autoComplete="current-password" className={inputClass} placeholder={t(locale, 'ui.passwordCurrent')} required />
      <Notice error>{deleteError}</Notice><Notice>{deleteMessage}</Notice>
      <button className="rounded-xl bg-rose-700 px-4 py-3 text-sm font-semibold text-white hover:bg-rose-800" disabled={deletion.isPending}>{t(locale, 'ui.deleteAccount')}</button>
    </form>
  </div>;
}

function StudentManager({ slug, courseName, classGroup, isArchived, students, user }: {
  slug: string;
  courseName: string;
  classGroup: string;
  isArchived: boolean;
  students: AnyRecord[];
  user: SessionUser;
}) {
  const locale = useLocale();
  const [query, setQuery] = useState('');
  const [searchTerm, setSearchTerm] = useState('');
  const [selected, setSelected] = useState<AnyRecord | null>(null);
  const [suggestionsOpen, setSuggestionsOpen] = useState(false);
  const [activeSuggestion, setActiveSuggestion] = useState(-1);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const router = useRouter();
  const canAdd = !isArchived && (user.role === 'Laboran' || user.role === 'Aslab');
  useEffect(() => {
    if (!canAdd || selected || query.trim().length < 3) return;
    const timer = setTimeout(() => setSearchTerm(query.trim()), 300);
    return () => clearTimeout(timer);
  }, [canAdd, query, selected]);
  const activeQuery = !selected && searchTerm === query.trim() && searchTerm.length >= 3;
  const found = trpc.courses.searchStudents.useQuery({ slug, query: searchTerm }, { enabled: canAdd && activeQuery });
  const suggestions = activeQuery ? found.data ?? [] : [];
  const add = trpc.courses.addStudent.useMutation();
  const remove = trpc.courses.removeStudent.useMutation();
  const canRemove = !isArchived && user.role === 'Laboran';

  function addSelected() {
    if (!selected) return;
    setError('');
    setMessage('');
    add.mutate({ slug, studentId: selected.id }, {
      onSuccess: () => {
        setQuery('');
        setSelected(null);
        setMessage(t(locale, 'ui.studentAdded'));
        router.refresh();
      },
      onError: (reason) => setError(localizeServerMessage(locale, reason.message)),
    });
  }

  function chooseStudent(student: AnyRecord) {
    setSelected(student);
    setActiveSuggestion(-1);
    setSuggestionsOpen(false);
  }

  return <>
    <Heading
      title={t(locale, 'ui.studentList')}
      description={`${courseName} (${classGroup})`}
      actions={<Link href={`/courses/${slug}`} className={secondary}><ArrowLeft className="h-4 w-4" /> {t(locale, 'ui.backToCourseDetails')}</Link>}
    />
    <section className={`${card} mb-6 flex flex-wrap items-center justify-between gap-4 px-5 py-4`}>
      <div className="flex items-center gap-4">
        <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><Users className="h-6 w-6" /></span>
        <div><p className="text-xs font-semibold text-slate-400">{t(locale, 'ui.totalStudents')}</p><p className="text-2xl font-semibold leading-none text-slate-800">{students.length} <span className="text-sm text-slate-400">{t(locale, 'ui.person')}</span></p></div>
      </div>
    </section>
    {canAdd && <section className="mb-8 rounded-3xl border border-emerald-100 bg-emerald-50/70 p-5 shadow-sm sm:p-6">
      <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div className="lg:max-w-sm">
          <p className="text-xs font-semibold text-emerald-600">{t(locale, 'ui.manageRoster')}</p>
          <h2 className="mt-2 text-xl font-semibold text-slate-900">{t(locale, 'ui.addStudent')}</h2>
          <p className="mt-1 text-sm text-slate-500">{t(locale, 'ui.searchStudentHint')}</p>
        </div>
        <div className="w-full lg:max-w-2xl">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-start">
            <div className="relative flex-1" onBlur={(event) => {
              const target = event.relatedTarget;
              if (!(target instanceof Node) || !event.currentTarget.contains(target)) setSuggestionsOpen(false);
            }}>
              <label htmlFor="student_search" className="sr-only">{t(locale, 'ui.searchStudent')}</label>
              <div className="relative">
                <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                  id="student_search"
                  type="search"
                  autoComplete="off"
                  role="combobox"
                  aria-expanded={Boolean(suggestionsOpen && !selected && query.trim().length >= 3)}
                  aria-controls="student_suggestions"
                  aria-activedescendant={activeSuggestion >= 0 ? `student-option-${activeSuggestion}` : undefined}
                  value={selected ? `${selected.id} — ${selected.name}` : query}
                  onFocus={() => setSuggestionsOpen(true)}
                  onChange={(event) => { setSelected(null); setQuery(event.target.value); setActiveSuggestion(-1); setSuggestionsOpen(true); setError(''); }}
                  onKeyDown={(event) => {
                    if (event.key === 'Escape') { setSuggestionsOpen(false); setActiveSuggestion(-1); }
                    else if (event.key === 'ArrowDown' && suggestions.length) { event.preventDefault(); setSuggestionsOpen(true); setActiveSuggestion((current) => Math.min(current + 1, suggestions.length - 1)); }
                    else if (event.key === 'ArrowUp' && suggestions.length) { event.preventDefault(); setSuggestionsOpen(true); setActiveSuggestion((current) => Math.max(current - 1, 0)); }
                    else if (event.key === 'Enter' && activeSuggestion >= 0 && suggestions[activeSuggestion]) { event.preventDefault(); chooseStudent(suggestions[activeSuggestion]); }
                  }}
                  className={`${inputClass} pl-11`}
                  placeholder={t(locale, 'ui.studentSearchPlaceholder')}
                />
              </div>
              {query.trim().length > 0 && query.trim().length < 3 && <p className="mt-2 text-xs font-semibold text-slate-500">{t(locale, 'ui.minimumThree')}</p>}
              {!selected && suggestionsOpen && query.trim().length >= 3 && <div id="student_suggestions" role="listbox" className="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl">
                {(!activeQuery || found.isFetching) && <p className="px-4 py-3 text-sm font-semibold text-slate-500">{t(locale, 'ui.searchingStudents')}</p>}
                {found.isError && <p className="px-4 py-3 text-sm font-semibold text-rose-600">{t(locale, 'ui.studentSearchFailed')}</p>}
                {activeQuery && !found.isFetching && !found.isError && found.data?.length === 0 && <p className="px-4 py-3 text-sm font-semibold text-slate-500">{t(locale, 'ui.noMatchingStudent')}</p>}
                {(activeQuery ? found.data : undefined)?.map((student, index) => <button
                  key={student.id}
                  type="button"
                  role="option"
                  id={`student-option-${index}`}
                  aria-selected={index === activeSuggestion}
                  onClick={() => chooseStudent(student)}
                  className={`flex w-full items-center justify-between gap-4 rounded-xl px-4 py-3 text-left transition hover:bg-emerald-50 focus:bg-emerald-50 focus:outline-none ${index === activeSuggestion ? 'bg-emerald-50' : ''}`}
                ><span className="min-w-0"><span className="block truncate text-sm font-semibold text-slate-800">{student.name}</span><span className="block truncate text-xs text-slate-400">{student.email}</span></span><span className="shrink-0 text-xs font-bold text-emerald-700">{student.id}</span></button>)}
              </div>}
            </div>
            <button type="button" onClick={addSelected} disabled={!selected || add.isPending} className={primary}>+ {t(locale, 'ui.addToCourse')}</button>
          </div>
        </div>
      </div>
    </section>}
    <Notice error>{error}</Notice><Notice>{message}</Notice>
    <div className={`${card} overflow-hidden`}>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[720px] border-collapse text-left text-sm">
          <thead className="border-b border-gray-100 bg-gray-50/50 text-xs font-semibold text-gray-400"><tr>
            <th className="whitespace-nowrap px-6 py-5 sm:px-8">No</th><th className="whitespace-nowrap px-6 py-5 sm:px-8">{t(locale, 'ui.students')}</th><th className="whitespace-nowrap px-6 py-5 sm:px-8">NIM</th>
            {canRemove && <th className="whitespace-nowrap px-6 py-5 text-center sm:px-8">{t(locale, 'ui.action')}</th>}
          </tr></thead>
          <tbody className="divide-y divide-gray-50">
            {students.map((student, index) => <tr key={student.id} className="transition hover:bg-gray-50/50">
              <td className="whitespace-nowrap px-6 py-5 text-sm font-bold text-gray-500 sm:px-8">{index + 1}</td>
              <td className="whitespace-nowrap px-6 py-5 sm:px-8"><div className="flex items-center gap-4">
                <div className="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-slate-100 text-xs font-semibold text-slate-400">{String(student.name).slice(0, 2).toUpperCase()}</div>
                <span className="text-sm font-semibold tracking-tight text-gray-800">{student.name}</span>
              </div></td>
              <td className="whitespace-nowrap px-6 py-5 sm:px-8"><span className="font-bold text-emerald-700">{student.id}</span></td>
              {canRemove && <td className="whitespace-nowrap px-6 py-5 text-center sm:px-8"><button className="inline-flex items-center gap-2 rounded-xl bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-600 shadow-sm transition hover:bg-rose-600 hover:text-white" onClick={() => {
                void confirmAppAction(t(locale, 'ui.studentRemovedConfirm'), copy(locale, 'ui.studentRemovedText', { name: student.name }), 'warning', locale).then((confirmed) => {
                  if (confirmed) remove.mutate({ slug, studentId: student.id }, { onSuccess: () => router.refresh(), onError: (reason) => setError(localizeServerMessage(locale, reason.message)) });
                });
              }}>{t(locale, 'ui.remove')}</button></td>}
            </tr>)}
            {!students.length && <tr><td colSpan={canRemove ? 4 : 3} className="p-12 text-center"><div className="mb-4 inline-flex rounded-3xl bg-slate-50 p-6 text-slate-300"><Users className="h-12 w-12" /></div><p className="text-xs font-semibold text-gray-400">{t(locale, 'ui.noEnrolledStudents')}</p></td></tr>}
          </tbody>
        </table>
      </div>
    </div>
  </>;
}
function ModuleEditor({ data }: { data: AnyRecord }) {
  const locale = useLocale();
  const [modules, setModules] = useState<AnyRecord[]>(() => data.meetings.map((row: AnyRecord) => ({
    id: row.id,
    meetingNumber: row.meetingNumber,
    title: row.title,
    description: row.description ?? '',
    moduleDriveLink: row.moduleDriveLink ?? '',
    deadline: row.deadline ? toLocalDateTime(row.deadline) : '',
    published: Boolean(row.publishedAt),
    openedBefore: Boolean(row.publishedAt),
  })));
  const mutation = trpc.courses.updateModules.useMutation();
  const router = useRouter();
  const [deletedModuleIds, setDeletedModuleIds] = useState<number[]>([]);
  const [resetModuleIds, setResetModuleIds] = useState<number[]>([]);
  const [error, setError] = useState('');
  const openCount = modules.filter((row) => row.published).length;
  const closedCount = modules.length - openCount;
  const nextNumber = Math.max(0, ...data.meetings.map((item: AnyRecord) => Number(item.meetingNumber)), ...modules.map((item) => Number(item.meetingNumber))) + 1;

  function change(index: number, key: string, value: string | boolean) {
    setModules((current) => current.map((row, i) => i === index ? { ...row, [key]: value } : row));
  }

  function addModule() {
    if (modules.length >= 16) {
      void showAppAlert('info', t(locale, 'module.limitTitle'), t(locale, 'module.limitText'), locale);
      return;
    }
    setModules((current) => [...current, {
      id: null,
      meetingNumber: nextNumber,
      title: `${locale === 'en' ? 'Module' : 'Modul'} ${nextNumber}`,
      description: '',
      moduleDriveLink: '',
      deadline: '',
      published: false,
      openedBefore: false,
    }]);
  }

  function removeModule(index: number) {
    const row = modules[index];
    if (!row) return;
    const text = t(locale, row.openedBefore || row.published ? 'module.deletePublishedText' : 'module.deleteUnopenedText');
    void confirmAppAction(t(locale, 'module.deleteConfirm'), text, 'warning', locale).then((confirmed) => {
      if (!confirmed) return;
      const id = row.id === null ? null : Number(row.id);
      if (id !== null) {
        setDeletedModuleIds((current) => current.includes(id) ? current : [...current, id]);
        setResetModuleIds((current) => current.filter((resetId) => resetId !== id));
      }
      setModules((current) => current.filter((_, currentIndex) => currentIndex !== index));
    });
  }

  function resetModule(index: number) {
    const row = modules[index];
    if (!row || row.id === null || !row.openedBefore || row.resetPending) return;
    void confirmAppAction(t(locale, 'module.clearConfirm'), t(locale, 'module.clearText'), 'warning', locale).then((confirmed) => {
      if (!confirmed) return;
      const id = Number(row.id);
      setResetModuleIds((current) => current.includes(id) ? current : [...current, id]);
      setModules((current) => current.map((item, currentIndex) => currentIndex === index ? {
        ...item,
        title: `Modul ${item.meetingNumber}`,
        description: '',
        moduleDriveLink: '',
        deadline: '',
        published: false,
        resetPending: true,
      } : item));
    });
  }

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError('');
    mutation.mutate({
      slug: data.slug,
      modules: modules.map((row) => ({
        id: row.id === null ? null : Number(row.id),
        title: String(row.title),
        description: row.description || null,
        moduleDriveLink: row.moduleDriveLink || null,
        deadline: row.deadline ? new Date(row.deadline) : null,
        published: Boolean(row.published),
      })),
      deletedModuleIds,
      resetModuleIds,
    }, { onSuccess: async (result) => {
      setModules(result.modules.map((row: AnyRecord) => ({
        id: row.id,
        meetingNumber: row.meetingNumber,
        title: row.title,
        description: row.description ?? '',
        moduleDriveLink: row.moduleDriveLink ?? '',
        deadline: row.deadline ? toLocalDateTime(row.deadline) : '',
        published: Boolean(row.publishedAt),
        openedBefore: Boolean(row.publishedAt),
      })));
      setDeletedModuleIds([]);
      setResetModuleIds([]);
      await showAppAlert('success', t(locale, 'module.savedTitle'), t(locale, 'module.savedText'), locale);
      router.refresh();
    }, onError: (reason) => setError(localizeServerMessage(locale, reason.message)) });
  }

  const saveButton = () => <button type="submit" form="module-editor-form" className={primary} disabled={mutation.isPending}>
    {mutation.isPending ? <><span className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />{t(locale, 'module.saving')}</> : <><Check className="h-4 w-4" />{t(locale, 'module.save')}</>}
  </button>;

  return <>
    <Heading
      eyebrow={t(locale, 'module.eyebrow')}
      title={`${t(locale, 'module.title')} · ${data.courseName}`}
      description={t(locale, 'module.description')}
      actions={<div className="flex flex-col gap-2 2xl:flex-row 2xl:items-center"><Link href={`/courses/${data.slug}`} className={secondary}><ArrowLeft className="h-4 w-4" />{t(locale, 'module.back')}</Link><button type="button" className={secondary} onClick={addModule} disabled={mutation.isPending || modules.length >= 16} title={modules.length >= 16 ? t(locale, 'module.limitText') : undefined}><Plus className="h-4 w-4" />{t(locale, 'module.add')}</button>{modules.length >= 16 && <p className="max-w-48 text-xs leading-5 text-slate-500">{t(locale, 'module.limitText')}</p>}{saveButton()}</div>}
    />

    <Notice error>{error}</Notice>
    <div className="mb-5 grid gap-3 sm:grid-cols-3" aria-label={locale === 'id' ? 'Ringkasan modul' : 'Module summary'}>
      <div className={`${card} flex items-center gap-3 px-4 py-3`}><span className="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700"><BookOpen className="h-5 w-5" /></span><div><p className="text-xl font-bold leading-none text-slate-900">{modules.length}</p><p className="mt-1 text-xs font-medium text-slate-600">{t(locale, 'module.count')}</p></div></div>
      <div className={`${card} flex items-center gap-3 px-4 py-3`}><span className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800"><LockKeyholeOpen className="h-5 w-5" /></span><div><p className="text-xl font-bold leading-none text-slate-900">{openCount}</p><p className="mt-1 text-xs font-medium text-slate-600">{t(locale, 'module.openCount')}</p></div></div>
      <div className={`${card} flex items-center gap-3 px-4 py-3`}><span className="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><LockKeyhole className="h-5 w-5" /></span><div><p className="text-xl font-bold leading-none text-slate-900">{closedCount}</p><p className="mt-1 text-xs font-medium text-slate-600">{t(locale, 'module.closeCount')}</p></div></div>
    </div>

    <form id="module-editor-form" onSubmit={submit} className="space-y-4">
      {modules.map((row, index) => {
        const status = row.published ? 'module.open' : 'module.closed';
        const switchLabel = `${t(locale, 'module.cardLabel')} ${row.meetingNumber} · ${t(locale, 'module.submission')}: ${t(locale, status)}`;
        return <section key={row.id ?? `new-${row.meetingNumber}`} className={`${card} overflow-hidden p-4 sm:p-6`}>
          <div className="flex flex-col gap-4 border-b border-slate-100 pb-5 md:flex-row md:items-start md:justify-between">
            <div className="min-w-0">
              <p className="text-xs font-bold uppercase tracking-[0.14em] text-slate-500">{t(locale, 'module.cardLabel')} {String(row.meetingNumber).padStart(2, '0')}</p>
              <h2 className="mt-1 text-lg font-semibold text-slate-900">{localizedModuleTitle(locale, row.title, row.meetingNumber)}</h2>
            </div>
            <div className="flex flex-col gap-2 sm:flex-row sm:items-start">
              <div className="flex flex-wrap gap-2">
                {row.openedBefore && row.id !== null && !row.resetPending && <button type="button" className="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-white px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-60" onClick={() => resetModule(index)} disabled={mutation.isPending}>
                  <RotateCcw className="h-4 w-4" />{t(locale, 'module.clearModule')}
                </button>}
                <button type="button" aria-label={`${t(locale, 'module.deleteModule')} ${row.meetingNumber}`} title={t(locale, 'module.deleteModule')} className="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60" onClick={() => removeModule(index)} disabled={mutation.isPending}>
                  <Trash2 className="h-4 w-4" />{t(locale, 'module.deleteModule')}
                </button>
              </div>
              <div className={`flex w-full items-center justify-between gap-4 rounded-xl border p-3 sm:w-auto sm:min-w-[330px] ${row.published ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50'}`}>
              <div className="min-w-0">
                <div className="flex items-center gap-2">
                  <span className={`h-2 w-2 shrink-0 rounded-full ${row.published ? 'bg-emerald-600' : 'bg-slate-400'}`} />
                  <p className="text-sm font-semibold text-slate-900">{t(locale, 'module.submission')}</p>
                  <span className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${row.published ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-200 text-slate-700'}`}>{t(locale, status)}</span>
                </div>
                <p className="mt-1 max-w-64 text-xs leading-5 text-slate-600">{t(locale, row.published ? 'module.openHelp' : 'module.closedHelp')}</p>
              </div>
              <button type="button" role="switch" aria-checked={Boolean(row.published)} aria-label={switchLabel} onClick={() => change(index, 'published', !row.published)} disabled={mutation.isPending || Boolean(row.resetPending)} className={`relative inline-flex h-7 w-12 shrink-0 items-center rounded-full p-1 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 ${row.published ? 'bg-emerald-700' : 'bg-slate-400'}`}>
                <span className={`h-5 w-5 rounded-full bg-white shadow-sm transition-transform ${row.published ? 'translate-x-5' : 'translate-x-0'}`} />
              </button>
            </div>
            </div>
          </div>

          <div className="grid gap-x-5 gap-y-4 pt-5 md:grid-cols-2">
        <label className="block">
              <span className="mb-1.5 block text-sm font-semibold text-slate-800">{t(locale, 'module.titleField')} <span className="text-rose-700" aria-label={locale === 'id' ? 'wajib' : 'required'}>*</span></span>
              <input className={inputClass} value={localizedModuleTitle(locale, row.title, row.meetingNumber)} onChange={(event) => change(index, 'title', event.target.value)} maxLength={255} required disabled={mutation.isPending || Boolean(row.resetPending)} />
              <span className="mt-1.5 block text-xs text-slate-500">{t(locale, 'module.titleHint')}</span>
            </label>
            <label className="block">
              <span className="mb-1.5 block text-sm font-semibold text-slate-800">{t(locale, 'module.deadline')} <span className="ml-1 font-normal text-slate-500">({t(locale, 'module.optional')})</span></span>
              <input type="datetime-local" className={inputClass} value={row.deadline} onChange={(event) => change(index, 'deadline', event.target.value)} disabled={mutation.isPending || Boolean(row.resetPending)} />
              <span className="mt-1.5 block text-xs text-slate-500">{t(locale, 'module.deadlineHint')}</span>
            </label>
            <label className="block md:col-span-2">
              <span className="mb-1.5 block text-sm font-semibold text-slate-800">{t(locale, 'module.material')} <span className="ml-1 font-normal text-slate-500">({t(locale, 'module.optional')})</span></span>
              <input type="url" className={inputClass} value={row.moduleDriveLink} onChange={(event) => change(index, 'moduleDriveLink', event.target.value)} placeholder="https://drive.google.com/..." disabled={mutation.isPending || Boolean(row.resetPending)} />
              <span className="mt-1.5 block text-xs text-slate-500">{t(locale, 'module.materialHint')}</span>
            </label>
            <label className="block md:col-span-2">
              <span className="mb-1.5 block text-sm font-semibold text-slate-800">{t(locale, 'module.descriptionField')} <span className="ml-1 font-normal text-slate-500">({t(locale, 'module.optional')})</span></span>
              <textarea className={inputClass} rows={4} maxLength={10000} value={row.description} onChange={(event) => change(index, 'description', event.target.value)} disabled={mutation.isPending || Boolean(row.resetPending)} />
              <span className="mt-1.5 block text-xs text-slate-500">{t(locale, 'module.descriptionHint')}</span>
            </label>
          </div>
        </section>;
      })}
      <div className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <p className="text-sm text-slate-600">{t(locale, 'module.saveHint')}</p>
        {saveButton()}
      </div>
    </form>
  </>;
}

function toLocalDateTime(value: string | Date) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return new Date(date.getTime() - date.getTimezoneOffset() * 60_000).toISOString().slice(0, 16);
}

function UserManager({ currentUserId }: { currentUserId: string }) {
  const locale = useLocale();
  const router = useRouter();
  const utils = trpc.useUtils();
  const [search, setSearch] = useState('');
  const [role, setRole] = useState('');
  const [error, setError] = useState('');
  const [editing, setEditing] = useState<{ id: string; name: string; email: string } | null>(null);
  const editDialogRef = useRef<HTMLDialogElement>(null);
  const query = trpc.users.list.useQuery({
    search,
    roles: role ? [role as 'Mahasiswa' | 'Dosen' | 'Laboran' | 'Aslab'] : [],
    limit: 25,
    offset: 0,
  }, { placeholderData: (previous) => previous });
  const updateRole = trpc.users.updateRole.useMutation();
  const updateAccount = trpc.users.updateAccount.useMutation();
  const deleteAccount = trpc.users.deleteAccount.useMutation();
  const reset = trpc.users.resetPassword.useMutation();
  const roles = ['Dosen', 'Mahasiswa', 'Laboran', 'Aslab'] as const;
  const roleText = (value: string) => t(locale, ('role.' + value) as Parameters<typeof t>[1]);

  useEffect(() => {
    const dialog = editDialogRef.current;
    if (!dialog) return;
    if (editing && !dialog.open) dialog.showModal();
    else if (!editing && dialog.open) dialog.close();
  }, [editing]);

  function refreshUsers() {
    void utils.users.list.invalidate();
    router.refresh();
  }

  function handleError(reason: { message: string }) {
    const message = localizeServerMessage(locale, reason.message);
    setError(message);
    void showAppAlert('error', t(locale, 'ui.couldNotProcess'), message, locale);
  }

  function startEditing(item: { id: string; name: string; email: string }) {
    setError('');
    setEditing({ id: item.id, name: item.name, email: item.email });
  }

  function saveAccount(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!editing) return;
    setError('');
    updateAccount.mutate({ id: editing.id, name: editing.name, email: editing.email }, {
      onSuccess: () => {
        setEditing(null);
        refreshUsers();
        void showAppAlert('success', t(locale, 'ui.actionSucceeded'), t(locale, 'ui.userAccountUpdated'), locale);
      },
      onError: handleError,
    });
  }

  function askToDelete(item: { id: string; name: string }) {
    setError('');
    void confirmAppAction(
      t(locale, 'ui.deleteAccountConfirm'),
      copy(locale, 'ui.deleteAccountText', { name: item.name }),
      'warning',
      locale,
    ).then((confirmed) => {
      if (!confirmed) return;
      deleteAccount.mutate({ id: item.id }, {
        onSuccess: () => {
          refreshUsers();
          void showAppAlert('success', t(locale, 'ui.actionSucceeded'), t(locale, 'ui.userAccountDeleted'), locale);
        },
        onError: handleError,
      });
    });
  }

  return <>
    <Heading title={t(locale, 'ui.userManagement')} description={copy(locale, 'ui.accountsFound', { count: query.data?.total ?? 0 })} />
    <div className={`${card} mb-5 flex flex-col gap-3 p-4 sm:flex-row`}>
      <input className={inputClass} value={search} onChange={(event) => setSearch(event.target.value)} placeholder={t(locale, 'ui.searchIdNameEmail')} />
      <select value={role} onChange={(event) => setRole(event.target.value)} className={inputClass}>
        <option value="">{t(locale, 'ui.allRoles')}</option>
        {roles.map((item) => <option key={item} value={item}>{roleText(item)}</option>)}
      </select>
    </div>
    <Notice error>{error || (query.error ? localizeServerMessage(locale, query.error.message) : '')}</Notice>
    <div className={`${card} overflow-x-auto`}>
      <table className="w-full min-w-[1050px] text-left text-sm">
        <thead className="bg-slate-50 text-xs uppercase text-slate-500">
          <tr>
            <th className="px-5 py-4">{t(locale, 'ui.user')}</th>
            <th className="px-5 py-4">{t(locale, 'ui.role')}</th>
            <th className="px-5 py-4">{t(locale, 'ui.account')}</th>
            <th className="px-5 py-4">{t(locale, 'ui.changeRole')}</th>
            <th className="px-5 py-4">{t(locale, 'ui.action')}</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {query.data?.users.map((item) => <Fragment key={item.id}>
            <tr key={item.id}>
              <td className="px-5 py-4">
                <p className="font-semibold">{item.name}</p>
                <p className="mt-1 text-xs text-slate-500">{item.id} · {item.email}</p>
              </td>
              <td className="px-5 py-4">{roleText(item.role)}</td>
              <td className="px-5 py-4 text-xs">{item.approvedAt ? t(locale, 'ui.approved') : t(locale, 'ui.waiting')}</td>
              <td className="px-5 py-4">
                {item.id === currentUserId ? <span className="text-xs text-slate-400">—</span> : <select
                  aria-label={t(locale, 'ui.changeRole') + ' ' + item.name}
                  className="rounded-lg border border-slate-200 px-3 py-2"
                  defaultValue=""
                  disabled={updateRole.isPending || (item.role === 'Mahasiswa' && !item.approvedAt)}
                  title={item.role === 'Mahasiswa' && !item.approvedAt ? t(locale, 'ui.promoteRequiresApproval') : undefined}
                  onChange={(event) => {
                    const nextRole = event.currentTarget.value as (typeof roles)[number];
                    event.currentTarget.value = '';
                    if (!nextRole) return;
                    const message = copy(locale, 'ui.promoteRole', { name: item.name, role: roleText(nextRole) });
                    void confirmAppAction(copy(locale, 'ui.changeRoleTo', { role: roleText(nextRole) }), message, 'question', locale).then((confirmed) => {
                      if (!confirmed) return;
                      setError('');
                      updateRole.mutate({ id: item.id, role: nextRole }, {
                        onSuccess: () => {
                          refreshUsers();
                          void showAppAlert('success', t(locale, 'ui.actionSucceeded'), t(locale, 'ui.userRoleUpdated'), locale);
                        },
                        onError: handleError,
                      });
                    });
                  }}
                >
                  <option value="">{t(locale, 'ui.selectNewRole')}</option>
                  {roles.filter((value) => value !== item.role).map((value) => <option key={value} value={value}>{roleText(value)}</option>)}
                </select>}
              </td>
              <td className="px-5 py-4">
                <div className="flex flex-wrap gap-2">
                  <button type="button" className={secondary} disabled={updateAccount.isPending} aria-label={copy(locale, 'ui.editUser', { name: item.name })} onClick={() => startEditing(item)}>
                    {t(locale, 'ui.edit')}
                  </button>
                  <button type="button" className={secondary} disabled={reset.isPending} aria-label={t(locale, 'ui.resetPassword') + ' ' + item.name} onClick={() => {
                    void confirmAppAction(t(locale, 'ui.resetPasswordConfirm'), copy(locale, 'ui.resetPasswordText', { name: item.name }), 'warning', locale).then((confirmed) => {
                      if (!confirmed) return;
                      setError('');
                      reset.mutate({ id: item.id }, {
                        onSuccess: () => {
                          refreshUsers();
                          void showAppAlert('success', t(locale, 'ui.actionSucceeded'), copy(locale, 'ui.passwordReset', { name: item.name }), locale);
                        },
                        onError: handleError,
                      });
                    });
                  }}>{t(locale, 'ui.resetPassword')}</button>
                  {item.id !== currentUserId && <button type="button" className="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 disabled:opacity-60" disabled={deleteAccount.isPending} aria-label={copy(locale, 'ui.deleteUser', { name: item.name })} onClick={() => askToDelete(item)}>
                    {t(locale, 'ui.delete')}
                  </button>}
                </div>
              </td>
            </tr>
          </Fragment>)}
        </tbody>
      </table>
    </div>
    {editing && <dialog
      ref={editDialogRef}
      aria-labelledby="user-account-editor-title"
      className="fixed inset-0 m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-2xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/55"
      onCancel={(event) => { event.preventDefault(); setEditing(null); }}
      onClose={() => setEditing(null)}
    >
      <section className="p-5 sm:p-7">
        <header className="mb-5 flex items-start justify-between gap-4">
          <div>
            <h2 id="user-account-editor-title" className="text-lg font-bold text-slate-900">{copy(locale, 'ui.editUser', { name: editing.name })}</h2>
            <p className="mt-1 text-xs leading-5 text-slate-500">{t(locale, 'ui.accountIdImmutable')} <span className="font-semibold">{editing.id}</span></p>
          </div>
          <button type="button" className="rounded-lg px-3 py-1 text-2xl leading-none text-slate-500 hover:bg-slate-100 hover:text-slate-900 disabled:opacity-50" aria-label={t(locale, 'ui.cancel')} disabled={updateAccount.isPending} onClick={() => editDialogRef.current?.close()}>
            ×
          </button>
        </header>
        <form onSubmit={saveAccount} className="grid gap-4 sm:grid-cols-2">
          <label className="block">
            <span className="mb-2 block text-xs font-semibold text-slate-700">{t(locale, 'ui.name')}</span>
            <input className={inputClass} value={editing.name} onChange={(event) => setEditing((current) => current ? { ...current, name: event.target.value } : current)} minLength={2} maxLength={255} required />
          </label>
          <label className="block">
            <span className="mb-2 block text-xs font-semibold text-slate-700">{t(locale, 'auth.email')}</span>
            <input className={inputClass} type="email" value={editing.email} onChange={(event) => setEditing((current) => current ? { ...current, email: event.target.value } : current)} maxLength={255} required />
          </label>
          <div className="flex flex-wrap gap-2 sm:col-span-2">
            <button className={primary} disabled={updateAccount.isPending}>
              {updateAccount.isPending ? t(locale, 'ui.processing') : t(locale, 'ui.saveChanges')}
            </button>
            <button type="button" className={secondary} disabled={updateAccount.isPending} onClick={() => editDialogRef.current?.close()}>{t(locale, 'ui.cancel')}</button>
          </div>
        </form>
      </section>
    </dialog>}
  </>;
}

function ImportUsers() {
  const locale = useLocale();
  const [file, setFile] = useState<File | null>(null); const [message, setMessage] = useState(''); const [error, setError] = useState(''); const [busy, setBusy] = useState(false);
  async function submit(event: FormEvent<HTMLFormElement>) { event.preventDefault(); if (!file) return; setBusy(true); setError(''); setMessage(''); const body = new FormData(); body.set('file', file); try { const response = await fetch('/api/import-users', { method: 'POST', body }); const result = await response.json(); if (!response.ok) throw new Error(result.error ?? (locale === 'en' ? 'The import could not be processed.' : 'Import gagal diproses.')); const summary = locale === 'en' ? `${result.success.length} accounts created. ${result.failures.length} rows skipped.` : `${result.success.length} akun berhasil dibuat. ${result.failures.length} baris dilewati.`; setMessage(summary); if (result.failures.length) { setError(result.failures.map((row: AnyRecord) => `${row.id}: ${localizeServerMessage(locale, row.message)}`).join(' · ')); await showAppAlert('warning', locale === 'en' ? 'Import completed with skipped rows' : 'Import selesai dengan baris terlewati', summary, locale); } else await showAppAlert('success', locale === 'en' ? 'Import complete' : 'Import berhasil', summary, locale); } catch (reason) { const errorText = reason instanceof Error ? localizeServerMessage(locale, reason.message) : (locale === 'en' ? 'The import could not be processed.' : 'Import gagal diproses.'); setError(errorText); await showAppAlert('error', locale === 'en' ? 'Import failed' : 'Import gagal', errorText, locale); } finally { setBusy(false); } }
  return <><Heading title={t(locale, 'ui.importUsers')} description={t(locale, 'ui.importDescription')} /><form onSubmit={submit} className={`${card} max-w-2xl space-y-4 p-5 sm:p-7`}><input type="file" accept=".xlsx,.xls,.csv" onChange={(event) => setFile(event.target.files?.[0] ?? null)} className={inputClass} required /><p className="text-xs leading-5 text-slate-500">{t(locale, 'ui.initialPasswordNote')}</p><Notice>{message}</Notice><Notice error>{error}</Notice><button className={primary} disabled={!file || busy}>{busy ? t(locale, 'ui.processing') : t(locale, 'ui.importUsers')} <ArrowDownToLine className="h-4 w-4" /></button></form></>;
}

function FinalTaskManager({ data }: { data: AnyRecord }) {
  const locale = useLocale();
  const [description, setDescription] = useState(data.task.description); const [deadline, setDeadline] = useState(data.task.deadline ? new Date(data.task.deadline).toISOString().slice(0, 16) : ''); const mutation = trpc.meetings.finalTaskUpdate.useMutation(); const router = useRouter(); const [error, setError] = useState('');
  return <><Heading title={t(locale, 'ui.manageFinalReport')} description={`${data.course.courseName} · ${t(locale, 'ui.class')} ${data.course.classGroup}`} /><section className={`${card} p-5 sm:p-7`}><form className="space-y-4" onSubmit={(event) => { event.preventDefault(); mutation.mutate({ id: data.task.id, description, ...(deadline ? { deadline: new Date(deadline) } : {}) }, { onSuccess: () => router.refresh(), onError: (reason) => setError(localizeServerMessage(locale, reason.message)) }); }}><Notice error>{error}</Notice><label className="block"><span className="mb-2 block text-xs font-semibold">{t(locale, 'ui.finalInstructions')}</span><textarea rows={6} value={description} onChange={(event) => setDescription(event.target.value)} className={inputClass} required /></label><label className="block max-w-md"><span className="mb-2 block text-xs font-semibold">{t(locale, 'ui.deadline')}</span><input type="datetime-local" value={deadline} onChange={(event) => setDeadline(event.target.value)} className={inputClass} /></label><button className={primary}>{t(locale, 'ui.saveSettings')}</button></form></section><div className="mt-6"><TablePage title={t(locale, 'ui.finalSubmissions')} description={t(locale, 'ui.finalSubmissionsDescription')} rows={data.students.map((student: AnyRecord) => ({ studentName: student.name, studentId: student.id, courseName: data.course.courseName, submission: data.submissions.find((item: AnyRecord) => item.studentId === student.id && item.isFinal) }))} type="meeting" /></div></>;
}

export function FeatureContent({ page, user, data }: { page: string; user: SessionUser; data: unknown }) {
  const locale = useLocale();
  const result = (data ?? {}) as AnyRecord;
  switch (page) {
    case 'dashboard': return <DashboardView data={result} user={user} />;
    case 'courses': return <CourseList data={result} user={user} />;
    case 'archives': return <CourseList data={result} user={user} archived />;
    case 'course-create': return <CourseCreate options={result} />;
    case 'course-edit': return <CourseCreate options={result.options} editing={result} />;
    case 'course-staff': return <CourseCreate options={result.options} editing={result} staffMode />;
    case 'course-detail': return <CourseDetail data={result} user={user} />;
    case 'print-card': return <PracticumCard data={result} />;
    case 'attendance': return <AttendanceEditor rows={result.rows} meetingId={Number(result.meetingId)} />;
    case 'student-submission': return <StudentSubmission data={result} />;
    case 'student-final-submission': return <StudentSubmission data={result} final />;
    case 'review': return <ReviewPanel data={result} user={user} />;
    case 'pending-submissions': return <TablePage title={t(locale, 'ui.submitPendingReview')} description={t(locale, 'ui.pendingReviewDescription')} rows={result as unknown as AnyRecord[]} type="pending" />;
    case 'meeting-submissions': return <MeetingSubmissionsPage data={result} user={user} />;
    case 'my-submissions': return <TablePage title={t(locale, 'ui.mySubmissions')} description={t(locale, 'ui.mySubmissionsDescription')} rows={result as unknown as AnyRecord[]} type="mine" />;
    case 'semesters': return <SemesterManager rows={result as unknown as AnyRecord[]} />;
    case 'account-approvals': return <ApprovalManager data={result} />;
    case 'tutorials': return <TutorialManager rows={result as unknown as AnyRecord[]} isLaboran={user.role === 'Laboran'} />;
    case 'grades': return <CourseGrades rows={result.rows} slug={result.slug} course={result.course} user={user} />;
    case 'students': return <StudentManager slug={result.slug} courseName={result.courseName} classGroup={result.classGroup} isArchived={result.isArchived} students={result.students as AnyRecord[]} user={user} />;
    case 'module-editor': return <ModuleEditor data={result} />;
    case 'laprak-report': return <LaprakReport data={result as unknown as LaprakReportData} />;
    case 'users': return <UserManager currentUserId={user.id} />;
    case 'import-users': return <ImportUsers />;
    case 'final-task': return <FinalTaskManager data={result} />;
    case 'profile': return <><ProfilePanel user={user} /><ProfileExtras /></>;
    default: return <GenericPlaceholder page={page} />;
  }
}

function GenericPlaceholder({ page }: { page: string }) { const locale = useLocale(); return <><Heading title={page} /><Empty message={t(locale, 'ui.pageLoading')} /></>; }
