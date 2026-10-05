'use client';

import Link from 'next/link';
import { useState } from 'react';
import { ArrowLeft, Check, Download, Printer } from 'lucide-react';
import { t } from '@/lib/locale';
import { useLocale } from '@/lib/locale-context';
import type { GradeExportCourse, GradeExportRow } from '@/lib/grade-export';
import { showAppAlert } from '@/lib/alerts';
import { localizeServerMessage } from '@/lib/locale';
import { trpc } from '@/trpc/react';
import { formatSemesterClassLabel } from '@/lib/course-label';
import type { SessionUser } from '@/server/auth/session';

type ReportCourse = GradeExportCourse & {
  slug: string;
  courseName: string;
  classGroup: string;
  targetSemester: number;
  semesterName: string;
  isArchived: boolean;
  semesterIsActive: boolean;
  meetings: Array<{ id: number; meetingNumber: number; title: string | null }>;
};

export type LaprakReportData = { course: ReportCourse; rows: GradeExportRow[] };

const card = 'rounded-2xl border border-slate-200 bg-white shadow-sm';
const secondary = 'inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-800 disabled:opacity-60';

function localSemester(locale: 'id' | 'en', value: string) {
  return locale === 'en' ? value.replace(/\bGanjil\b/gi, 'Odd').replace(/\bGenap\b/gi, 'Even') : value;
}

function moduleTitle(locale: 'id' | 'en', title: string | null, number: number) {
  const value = title || `Modul ${number}`;
  return locale === 'en' && value === `Modul ${number}` ? `Module ${number}` : value;
}

export function LaprakReport({ data, user }: { data: LaprakReportData; user: SessionUser }) {
  const locale = useLocale();
  const { course, rows } = data;
  const courseSlug = typeof course.slug === 'string' ? course.slug.trim() : '';
  const courseQuery = trpc.courses.get.useQuery({ slug: courseSlug }, { enabled: Boolean(courseSlug), refetchInterval: 30_000, refetchOnWindowFocus: true });
  const rowsQuery = trpc.courses.grades.useQuery({ slug: courseSlug }, { enabled: Boolean(courseSlug), refetchInterval: 15_000, refetchOnWindowFocus: true });
  const liveCourse = (courseQuery.data ?? course) as ReportCourse;
  const liveRows = (rowsQuery.data ?? rows) as GradeExportRow[];
  const [exporting, setExporting] = useState(false);
  const [drafts, setDrafts] = useState<Record<string, string>>({});
  const saveManualGrade = trpc.courses.saveManualModuleGrade.useMutation();
  const formatScore = (value: number | string | null | undefined) => value === null || value === undefined
    ? '—'
    : new Intl.NumberFormat(locale === 'en' ? 'en-US' : 'id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value));
  const editableRole = user.role === 'Aslab' || user.role === 'Laboran' ? user.role : null;
  const canEditGrades = Boolean(editableRole && !liveCourse.isArchived && liveCourse.semesterIsActive);

  async function exportWorkbook() {
    setExporting(true);
    try {
      const { downloadLaprakWorkbook } = await import('@/lib/laprak-export');
      await downloadLaprakWorkbook(liveRows, liveCourse, locale);
      await showAppAlert('success', t(locale, 'ui.laprakExported'), t(locale, 'ui.laprakExportComplete'), locale);
    } catch {
      await showAppAlert('error', t(locale, 'ui.laprakExportError'), t(locale, 'ui.laprakExportError'), locale);
    } finally {
      setExporting(false);
    }
  }

  const modules = liveCourse.meetings ?? [];
  const possibleReports = liveRows.length * modules.length;
  const approvalsRecorded = liveRows.reduce((total, student) => total + student.modules.reduce((count, module) =>
    count + Number(module.hasAslabScore) + Number(module.hasLaboranScore), 0), 0);

  function saveGrade(student: GradeExportRow, meetingNumber: number, draftKey: string, fallback: number | string | null | undefined) {
    if (!editableRole) return;
    const value = drafts[draftKey] ?? (fallback === null || fallback === undefined ? '' : String(fallback));
    const score = Number(value);
    if (value.trim() === '' || !Number.isFinite(score) || score < 0 || score > 100) {
      void showAppAlert('error', t(locale, 'ui.couldNotProcess'), t(locale, 'ui.manualGradeRange'), locale);
      return;
    }
    saveManualGrade.mutate({ slug: liveCourse.slug, studentId: student.id, meetingNumber, score }, {
      onSuccess: () => {
        setDrafts((current) => { const next = { ...current }; delete next[draftKey]; return next; });
        void rowsQuery.refetch();
        void showAppAlert('success', t(locale, 'ui.actionSucceeded'), t(locale, 'ui.manualGradeSaved'), locale);
      },
      onError: (reason) => { void showAppAlert('error', t(locale, 'ui.couldNotProcess'), localizeServerMessage(locale, reason.message), locale); },
    });
  }

  return <div className="mx-auto max-w-[110rem] space-y-6 px-0 sm:px-2">
    <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <Link href={`/courses/${liveCourse.slug}`} className="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700"><ArrowLeft className="h-4 w-4" />{t(locale, 'ui.backToCourseDetails')}</Link>
        <p className="text-xs font-bold uppercase tracking-[0.15em] text-emerald-800">SI Praktikum</p>
        <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{t(locale, 'ui.laprakSummary')}: {liveCourse.courseName}</h1>
        <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{t(locale, 'ui.laprakReportDescription')} {liveCourse.courseName} · {formatSemesterClassLabel(localSemester(locale, liveCourse.semesterName), liveCourse.targetSemester, liveCourse.classGroup)}</p>
      </div>
      <div className="flex flex-wrap gap-2">
        <button type="button" onClick={() => void exportWorkbook()} disabled={exporting} className={secondary}><Download className="h-4 w-4" />{exporting ? t(locale, 'ui.exportingLaprak') : t(locale, 'ui.exportLaprakExcel')}</button>
        <button type="button" onClick={() => window.print()} className={secondary}><Printer className="h-4 w-4" />{t(locale, 'ui.printPdf')}</button>
      </div>
    </div>

    <section className="grid gap-3 sm:grid-cols-3">
      <article className={`${card} p-4`}><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t(locale, 'ui.students')}</p><p className="mt-1 text-2xl font-bold text-slate-900">{liveRows.length}</p></article>
      <article className={`${card} p-4`}><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t(locale, 'ui.moduleOverview')}</p><p className="mt-1 text-2xl font-bold text-slate-900">{modules.length}</p></article>
      <article className={`${card} p-4`}><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t(locale, 'ui.reviewerApprovals')}</p><p className="mt-1 text-2xl font-bold text-emerald-700">{approvalsRecorded}<span className="ml-1 text-sm font-medium text-slate-500">/ {possibleReports * 2}</span></p></article>
    </section>

    <div className={`${card} overflow-x-auto print:border-0 print:shadow-none`}><table className="w-full min-w-max border-collapse text-left text-xs">
      <thead><tr className="border-b border-slate-100 bg-slate-50 text-xs font-semibold text-slate-500"><th className="sticky left-0 z-10 min-w-[210px] bg-slate-50 px-5 py-4">{t(locale, 'ui.students')}</th>{modules.map((module) => <th className="min-w-[150px] whitespace-nowrap px-3 py-4 text-center" key={module.id} title={moduleTitle(locale, module.title, module.meetingNumber)}>{moduleTitle(locale, module.title, module.meetingNumber)}</th>)}<th className="min-w-[120px] bg-slate-100/70 px-4 py-4 text-center">{t(locale, 'ui.modulesApproved')}</th><th className="min-w-[130px] bg-emerald-50 px-4 py-4 text-center">{t(locale, 'ui.laprakGrade')}</th></tr></thead>
      <tbody className="divide-y divide-slate-100">{liveRows.map((student) => {
        const gradedCount = student.modules.filter((module) => module.isCompleted && module.hasAslabScore && module.hasLaboranScore).length;
        return <tr key={student.id} className="align-top transition hover:bg-slate-50/50">
          <td className="sticky left-0 z-[1] bg-white px-5 py-4"><div className="flex flex-col"><span className="text-sm font-bold text-slate-800">{student.name}</span><span className="text-xs text-slate-400">{student.id}</span></div></td>
          {modules.map((meeting) => {
            const moduleGrade = student.modules.find((item) => item.meetingNumber === meeting.meetingNumber);
            const currentScore = editableRole === 'Aslab' ? moduleGrade?.aslabScore : moduleGrade?.laboranScore;
            const currentStatus = editableRole === 'Aslab' ? moduleGrade?.aslabStatus : moduleGrade?.laboranStatus;
            const draftKey = `${student.id}:${meeting.meetingNumber}`;
            const value = drafts[draftKey] ?? (currentScore === null || currentScore === undefined ? '' : String(currentScore));
            const otherLabel = editableRole === 'Aslab' ? t(locale, 'ui.labAdministratorShort') : t(locale, 'ui.labAssistantShort');
            const otherScore = editableRole === 'Aslab' ? moduleGrade?.laboranScore : moduleGrade?.aslabScore;
            const otherStatus = editableRole === 'Aslab' ? moduleGrade?.laboranStatus : moduleGrade?.aslabStatus;
            return <td key={meeting.id} className="px-3 py-4 text-center"><div className="mx-auto flex min-h-[112px] max-w-[220px] flex-col items-center justify-center gap-2 rounded-lg bg-slate-50 p-2">
              {canEditGrades && editableRole ? <>
                <label className="w-full text-left text-[10px] font-semibold text-slate-600" htmlFor={`grade-${draftKey}`}>{editableRole === 'Aslab' ? t(locale, 'ui.labAssistantShort') : t(locale, 'ui.labAdministratorShort')}</label>
                <div className="flex w-full items-center gap-1.5">
                  <input id={`grade-${draftKey}`} type="number" min="0" max="100" step="0.01" inputMode="decimal" aria-label={`${t(locale, 'ui.moduleGrades')} · ${student.name}`} value={value}
                    onChange={(event) => setDrafts((current) => ({ ...current, [draftKey]: event.target.value }))}
                    className="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-2 py-2 text-center text-sm font-semibold text-slate-800 outline-none focus:border-emerald-500" />
                  <button type="button" aria-label={t(locale, 'ui.approveAndSaveGrade')} title={t(locale, 'ui.approveAndSaveGrade')} disabled={saveManualGrade.isPending}
                    onClick={() => saveGrade(student, meeting.meetingNumber, draftKey, currentScore)}
                    className={`inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white disabled:opacity-50 ${currentStatus === 'ACC' ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-slate-500 hover:bg-emerald-700'}`}><Check className="h-4 w-4" /></button>
                </div>
                <span className="text-[10px] text-slate-500">{otherLabel} {formatScore(otherScore)} {otherStatus === 'ACC' ? '✓' : '·'}</span>
              </> : <>
                <span className="text-xs font-semibold text-slate-700">{t(locale, 'ui.labAssistantShort')} {formatScore(moduleGrade?.aslabScore)} {moduleGrade?.aslabStatus === 'ACC' ? '✓' : '·'}</span>
                <span className="text-xs font-semibold text-slate-700">{t(locale, 'ui.labAdministratorShort')} {formatScore(moduleGrade?.laboranScore)} {moduleGrade?.laboranStatus === 'ACC' ? '✓' : '·'}</span>
                <span className="text-[10px] font-medium text-slate-500">{t(locale, 'ui.moduleGrades')}: {formatScore(moduleGrade?.score)}</span>
              </>}
            </div></td>;
          })}
          <td className="bg-slate-50/40 px-4 py-4 text-center"><span className="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">{gradedCount}/{modules.length}</span></td>
          <td className="bg-emerald-50/40 px-4 py-4 text-center">{student.laprak !== null && student.laprak !== undefined ? <span className="text-sm font-bold text-emerald-800">{formatScore(student.laprak)}</span> : <span className="text-xs text-slate-500">{t(locale, 'ui.reportIncomplete')}</span>}</td>
        </tr>;
      })}{!liveRows.length && <tr><td colSpan={modules.length + 3} className="px-6 py-14 text-center text-sm text-slate-500">{t(locale, 'ui.noParticipants')}</td></tr>}</tbody>
    </table></div>
    <p className="text-xs leading-5 text-slate-500">{t(locale, 'ui.laprakLiveUpdate')}</p>
  </div>;
}
