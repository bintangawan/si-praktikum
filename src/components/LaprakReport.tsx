'use client';

import Link from 'next/link';
import { useState } from 'react';
import { ArrowLeft, Check, Download, Printer } from 'lucide-react';
import { t } from '@/lib/locale';
import { useLocale } from '@/lib/locale-context';
import type { GradeExportCourse, GradeExportRow } from '@/lib/grade-export';
import { showAppAlert } from '@/lib/alerts';
import { trpc } from '@/trpc/react';

type ReportCourse = GradeExportCourse & {
  slug: string;
  courseName: string;
  classGroup: string;
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

export function LaprakReport({ data }: { data: LaprakReportData }) {
  const locale = useLocale();
  const { course, rows } = data;
  const courseQuery = trpc.courses.get.useQuery({ slug: course.slug }, { refetchInterval: 30_000, refetchOnWindowFocus: true });
  const rowsQuery = trpc.courses.grades.useQuery({ slug: course.slug }, { refetchInterval: 15_000, refetchOnWindowFocus: true });
  const liveCourse = (courseQuery.data ?? course) as ReportCourse;
  const liveRows = (rowsQuery.data ?? rows) as GradeExportRow[];
  const [exporting, setExporting] = useState(false);
  const formatScore = (value: number | string | null | undefined) => value === null || value === undefined
    ? '—'
    : new Intl.NumberFormat(locale === 'en' ? 'en-US' : 'id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value));

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
  const reportsReceived = liveRows.reduce((total, student) => total + student.modules.filter((module) => module.hasSubmission).length, 0);
  const possibleReports = liveRows.length * modules.length;

  return <div className="mx-auto max-w-[110rem] space-y-6 px-0 sm:px-2">
    <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <Link href={`/courses/${liveCourse.slug}`} className="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700"><ArrowLeft className="h-4 w-4" />{t(locale, 'ui.backToCourseDetails')}</Link>
        <p className="text-xs font-bold uppercase tracking-[0.15em] text-emerald-800">SI Praktikum</p>
        <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{t(locale, 'ui.laprakSummary')}: {liveCourse.courseName}</h1>
        <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{t(locale, 'ui.laprakReportDescription')} {liveCourse.courseName} · {t(locale, 'ui.class')} {liveCourse.classGroup} · {localSemester(locale, liveCourse.semesterName)}</p>
      </div>
      <div className="flex flex-wrap gap-2">
        <button type="button" onClick={() => void exportWorkbook()} disabled={exporting} className={secondary}><Download className="h-4 w-4" />{exporting ? t(locale, 'ui.exportingLaprak') : t(locale, 'ui.exportLaprakExcel')}</button>
        <button type="button" onClick={() => window.print()} className={secondary}><Printer className="h-4 w-4" />{t(locale, 'ui.printPdf')}</button>
      </div>
    </div>

    <section className="grid gap-3 sm:grid-cols-3">
      <article className={`${card} p-4`}><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t(locale, 'ui.students')}</p><p className="mt-1 text-2xl font-bold text-slate-900">{liveRows.length}</p></article>
      <article className={`${card} p-4`}><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t(locale, 'ui.moduleOverview')}</p><p className="mt-1 text-2xl font-bold text-slate-900">{modules.length}</p></article>
      <article className={`${card} p-4`}><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t(locale, 'ui.reportsReceived')}</p><p className="mt-1 text-2xl font-bold text-emerald-700">{reportsReceived}<span className="ml-1 text-sm font-medium text-slate-500">/ {possibleReports}</span></p></article>
    </section>

    <div className={`${card} overflow-x-auto print:border-0 print:shadow-none`}><table className="w-full min-w-max border-collapse text-left text-xs">
      <thead><tr className="border-b border-slate-100 bg-slate-50 text-xs font-semibold text-slate-500"><th className="sticky left-0 z-10 min-w-[210px] bg-slate-50 px-5 py-4">{t(locale, 'ui.students')}</th>{modules.map((module) => <th className="min-w-[150px] whitespace-nowrap px-3 py-4 text-center" key={module.id} title={moduleTitle(locale, module.title, module.meetingNumber)}>{moduleTitle(locale, module.title, module.meetingNumber)}</th>)}<th className="min-w-[120px] bg-slate-100/70 px-4 py-4 text-center">{t(locale, 'ui.reportsSubmitted')}</th><th className="min-w-[130px] bg-emerald-50 px-4 py-4 text-center">{t(locale, 'ui.laprakGrade')}</th></tr></thead>
      <tbody className="divide-y divide-slate-100">{liveRows.map((student) => {
        const submittedCount = student.modules.filter((module) => module.hasSubmission).length;
        const gradedCount = student.modules.filter((module) => module.isCompleted && module.hasAslabScore && module.hasLaboranScore).length;
        return <tr key={student.id} className="align-top transition hover:bg-slate-50/50">
          <td className="sticky left-0 z-[1] bg-white px-5 py-4"><div className="flex flex-col"><span className="text-sm font-bold text-slate-800">{student.name}</span><span className="text-xs text-slate-400">{student.id}</span></div></td>
          {modules.map((meeting) => {
            const moduleGrade = student.modules.find((item) => item.meetingNumber === meeting.meetingNumber);
            const submitted = Boolean(moduleGrade?.hasSubmission);
            return <td key={meeting.id} className="px-3 py-4 text-center"><div className={`mx-auto flex min-h-[62px] max-w-[190px] flex-col items-center justify-center rounded-lg px-2 py-1.5 ${submitted ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-50 text-slate-400'}`}>
              <span className="inline-flex items-center gap-1 font-bold">{submitted && <Check aria-hidden="true" className="h-4 w-4" />}{submitted ? t(locale, 'ui.reportReceived') : t(locale, 'ui.reportMissing')}</span>
              {submitted && moduleGrade && <><span className="mt-1 text-xs font-semibold">{t(locale, 'ui.moduleGrades')}: {formatScore(moduleGrade.score)}</span><span className="mt-0.5 text-[10px] leading-4 text-slate-600">{t(locale, 'ui.labAssistantShort')} {formatScore(moduleGrade.aslabScore)} · {t(locale, 'ui.labAdministratorShort')} {formatScore(moduleGrade.laboranScore)}</span></>}
            </div></td>;
          })}
          <td className="bg-slate-50/40 px-4 py-4 text-center"><span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-bold ${modules.length > 0 && submittedCount === modules.length ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-50 text-amber-800'}`}>{submittedCount}/{modules.length}</span><p className="mt-1 text-[10px] text-slate-500">{t(locale, 'ui.modulesGradedCount')}: {gradedCount}/{modules.length}</p></td>
          <td className="bg-emerald-50/40 px-4 py-4 text-center">{student.laprak !== null && student.laprak !== undefined ? <span className="text-sm font-bold text-emerald-800">{formatScore(student.laprak)}</span> : <span className="text-xs text-slate-500">{t(locale, 'ui.reportIncomplete')}</span>}</td>
        </tr>;
      })}{!liveRows.length && <tr><td colSpan={modules.length + 3} className="px-6 py-14 text-center text-sm text-slate-500">{t(locale, 'ui.noParticipants')}</td></tr>}</tbody>
    </table></div>
    <p className="text-xs leading-5 text-slate-500">{t(locale, 'ui.laprakLiveUpdate')}</p>
  </div>;
}
