import ExcelJS from 'exceljs';
import type { GradeExportCourse, GradeExportRow } from './grade-export';

type ExportLocale = 'id' | 'en';
type LaprakExportCourse = GradeExportCourse & {
  slug: string;
  meetings?: Array<{ meetingNumber: number; title?: string | null }>;
};

const labels = {
  id: {
    title: 'Rekap pengumpulan Laprak', subtitle: 'Rekap otomatis pengumpulan laporan praktikum dan nilai per modul.',
    course: 'Mata praktikum', class: 'Kelas', semester: 'Semester', exported: 'Tanggal ekspor', lecturer: 'Dosen',
    assistant: 'Aslab', administrator: 'Laboran', students: 'Jumlah mahasiswa', received: 'Laporan masuk', graded: 'Modul dinilai',
    reportGrade: 'Nilai Laprak', reportLetter: 'Predikat Laprak', detailTitle: 'Rincian pengumpulan dan nilai modul',
    detailSubtitle: 'Centang menunjukkan laporan sudah masuk. Nilai modul = 80% Aslab + 20% Laboran.',
    number: 'No.', nim: 'NIM', student: 'Nama mahasiswa', moduleNumber: (number: number) => `Modul ke-${number}`, moduleColumn: 'Modul ke-', moduleName: 'Nama modul',
    submission: 'Pengumpulan', yes: 'Sudah masuk', no: 'Belum masuk', assistantScore: 'Nilai Aslab',
    administratorScore: 'Nilai Laboran', moduleGrade: 'Nilai modul', review: 'Status penilaian', complete: 'Selesai dinilai',
    pending: 'Menunggu pemeriksaan/nilai', notSubmitted: 'Belum mengumpulkan', waitingScore: 'Menunggu nilai',
    unnamedModule: (number: number) => `Modul ${number}`, fileName: 'rekap-laprak',
  },
  en: {
    title: 'Laprak submission recap', subtitle: 'Automatic practicum report submission and module grade recap.',
    course: 'Practicum course', class: 'Class', semester: 'Semester', exported: 'Export date', lecturer: 'Lecturer',
    assistant: 'Lab assistant', administrator: 'Lab administrator', students: 'Student count', received: 'Reports received', graded: 'Modules graded',
    reportGrade: 'Laprak grade', reportLetter: 'Laprak letter', detailTitle: 'Module submission and grade details',
    detailSubtitle: 'A checkmark means a report was submitted. Module grade = 80% assistant + 20% administrator.',
    number: 'No.', nim: 'Student ID (NIM)', student: 'Student name', moduleNumber: (number: number) => `Module no. ${number}`, moduleColumn: 'Module no.', moduleName: 'Module name',
    submission: 'Submission', yes: 'Submitted', no: 'Not submitted', assistantScore: 'Assistant score',
    administratorScore: 'Administrator score', moduleGrade: 'Module grade', review: 'Grade status', complete: 'Graded',
    pending: 'Awaiting review/grade', notSubmitted: 'Not submitted', waitingScore: 'Awaiting grade',
    unnamedModule: (number: number) => `Module ${number}`, fileName: 'laprak-report',
  },
} satisfies Record<ExportLocale, Record<string, string | ((number: number) => string)>>;

type Copy = typeof labels.id;

function textCell(cell: ExcelJS.Cell, value: ExcelJS.CellValue, bold = false) {
  cell.value = value;
  cell.font = { name: 'Aptos', size: 10, bold, color: { argb: 'FF172B3A' } };
  cell.alignment = { vertical: 'middle', wrapText: true };
}

function title(sheet: ExcelJS.Worksheet, endColumn: string, heading: string, subtitle: string) {
  sheet.mergeCells(`A1:${endColumn}1`);
  sheet.mergeCells(`A2:${endColumn}2`);
  const headingCell = sheet.getCell('A1');
  headingCell.value = heading;
  headingCell.font = { name: 'Aptos Display', size: 18, bold: true, color: { argb: 'FFFFFFFF' } };
  headingCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF064E3B' } };
  headingCell.alignment = { vertical: 'middle', horizontal: 'left' };
  const subtitleCell = sheet.getCell('A2');
  subtitleCell.value = subtitle;
  subtitleCell.font = { name: 'Aptos', size: 10, italic: true, color: { argb: 'FF475569' } };
  subtitleCell.alignment = { vertical: 'middle', wrapText: true };
  sheet.getRow(1).height = 34;
  sheet.getRow(2).height = 25;
}

function metadata(sheet: ExcelJS.Worksheet, rowNumber: number, fields: Array<[string, string]>) {
  const positions = [1, 4, 7, 10];
  fields.forEach(([label, value], index) => {
    const column = positions[index];
    const labelCell = sheet.getCell(rowNumber, column);
    labelCell.value = label;
    labelCell.font = { name: 'Aptos', size: 9, bold: true, color: { argb: 'FF047857' } };
    labelCell.alignment = { vertical: 'middle', wrapText: true };
    sheet.mergeCells(rowNumber, column + 1, rowNumber, column + 2);
    const valueCell = sheet.getCell(rowNumber, column + 1);
    valueCell.value = value || '—';
    valueCell.font = { name: 'Aptos', size: 9, color: { argb: 'FF172B3A' } };
    valueCell.alignment = { vertical: 'middle', wrapText: true };
  });
  sheet.getRow(rowNumber).height = 28;
}

function header(row: ExcelJS.Row) {
  row.height = 36;
  row.eachCell((cell) => {
    cell.font = { name: 'Aptos', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF047857' } };
    cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
    cell.border = { bottom: { style: 'medium', color: { argb: 'FF064E3B' } } };
  });
}

function bodyRow(row: ExcelJS.Row, index: number, numericColumns: number[] = []) {
  row.height = 28;
  row.eachCell((cell, column) => {
    cell.font = { name: 'Aptos', size: 9, color: { argb: 'FF172B3A' } };
    cell.alignment = { vertical: 'middle', horizontal: numericColumns.includes(column) ? 'right' : 'left', wrapText: true };
    cell.border = { bottom: { style: 'hair', color: { argb: 'FFE2E8F0' } } };
    if (index % 2 === 1) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FDF4' } };
    if (numericColumns.includes(column)) cell.numFmt = '0.00';
  });
}

function localized(copy: Copy, key: keyof Copy, ...args: [] | [number]) {
  const value = copy[key];
  return typeof value === 'function' ? value(args[0] ?? 0) : value;
}

function submittedModuleCount(student: GradeExportRow) {
  return student.modules.filter((module) => module.hasSubmission).length;
}

function gradedModuleCount(student: GradeExportRow) {
  return student.modules.filter((module) => module.isCompleted && module.hasAslabScore && module.hasLaboranScore).length;
}

export function createLaprakWorkbook(rows: GradeExportRow[], course: LaprakExportCourse, locale: ExportLocale) {
  const copy = labels[locale] as Copy;
  const modules = [...(rows[0]?.modules ?? [])].sort((a, b) => a.meetingNumber - b.meetingNumber);
  const moduleCount = course.meetings?.length ?? modules.length;
  const moduleNumbers = course.meetings?.map((item) => item.meetingNumber) ?? modules.map((item) => item.meetingNumber);
  const displayedModules = moduleNumbers.map((number) => modules.find((item) => item.meetingNumber === number) ?? {
    meetingNumber: number, meetingTitle: course.meetings?.find((item) => item.meetingNumber === number)?.title ?? null, hasSubmission: false, isCompleted: false,
    hasAslabScore: false, hasLaboranScore: false, aslabScore: null, laboranScore: null, score: null,
  });
  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'SI Praktikum';
  workbook.subject = `${course.courseName} · ${course.classGroup}`;
  workbook.title = copy.title;
  workbook.created = new Date();

  const summary = workbook.addWorksheet(locale === 'id' ? 'Rekap Laprak' : 'Laprak Summary', {
    pageSetup: { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0 },
    views: [{ state: 'frozen', ySplit: 9, xSplit: 3 }],
  });
  const tableLastColumnNumber = 3 + moduleCount + 4;
  const lastColumnNumber = Math.max(tableLastColumnNumber, 12);
  const tableLastColumn = summary.getColumn(tableLastColumnNumber).letter;
  const lastColumn = summary.getColumn(lastColumnNumber).letter;
  const columns = [
    { width: 7 }, { width: 18 }, { width: 30 },
    ...displayedModules.map(() => ({ width: 20 })),
    { width: 17 }, { width: 16 }, { width: 16 }, { width: 14 },
  ];
  while (columns.length < lastColumnNumber) columns.push({ width: 14 });
  summary.columns = columns;
  title(summary, lastColumn, copy.title, copy.subtitle);
  const exportedAt = new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : 'id-ID', { dateStyle: 'long' }).format(new Date());
  metadata(summary, 4, [[copy.course, course.courseName], [copy.class, course.classGroup], [copy.semester, course.semesterName], [copy.exported, exportedAt]]);
  metadata(summary, 5, [[copy.lecturer, course.dosenName ?? '—'], [copy.assistant, course.aslabName ?? '—'], [copy.administrator, course.laboranName ?? '—'], [copy.students, String(rows.length)]]);
  summary.mergeCells(`A7:${lastColumn}7`);
  textCell(summary.getCell('A7'), copy.subtitle);
  summary.getCell('A7').font = { name: 'Aptos', size: 9, color: { argb: 'FF065F46' }, italic: true };
  summary.getCell('A7').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
  summary.getRow(7).height = 32;
  summary.getRow(8).height = 6;
  const headers = [copy.number, copy.nim, copy.student, ...displayedModules.map((module) => `${localized(copy, 'moduleNumber', module.meetingNumber)} ${module.meetingTitle || localized(copy, 'unnamedModule', module.meetingNumber)}`), copy.received, copy.graded, copy.reportGrade, copy.reportLetter];
  const summaryHeader = summary.addRow(headers);
  header(summaryHeader);
  rows.forEach((student, index) => {
    const cells = displayedModules.map((displayModule) => {
      const moduleGrade = student.modules.find((item) => item.meetingNumber === displayModule.meetingNumber);
      if (!moduleGrade?.hasSubmission) return '—';
      return `✓\n${moduleGrade.score === null ? localized(copy, 'waitingScore') : Number(moduleGrade.score).toFixed(2)}`;
    });
    const row = summary.addRow([
      index + 1, student.id, student.name, ...cells,
      `${submittedModuleCount(student)}/${moduleCount}`, `${gradedModuleCount(student)}/${moduleCount}`,
      student.laprak, student.laprakLetter,
    ]);
    bodyRow(row, index, [3 + moduleCount + 3]);
    displayedModules.forEach((displayModule, moduleIndex) => {
      const item = student.modules.find((module) => module.meetingNumber === displayModule.meetingNumber);
      const cell = row.getCell(4 + moduleIndex);
      cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
      if (item?.hasSubmission) {
        cell.font = { name: 'Aptos', size: 9, bold: true, color: { argb: 'FF047857' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
      } else {
        cell.font = { name: 'Aptos', size: 9, color: { argb: 'FF94A3B8' } };
      }
    });
  });
  summary.autoFilter = { from: 'A9', to: `${tableLastColumn}${Math.max(summaryHeader.number, summary.rowCount)}` };
  summary.pageSetup.printTitlesRow = '1:9';
  summary.pageSetup.horizontalCentered = true;

  const details = workbook.addWorksheet(locale === 'id' ? 'Rincian Modul' : 'Module Details', {
    pageSetup: { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0 },
    views: [{ state: 'frozen', ySplit: 11 }],
  });
  details.columns = [{ width: 7 }, { width: 18 }, { width: 30 }, { width: 13 }, { width: 34 }, { width: 20 }, { width: 18 }, { width: 20 }, { width: 17 }, { width: 25 }];
  title(details, 'J', copy.detailTitle, copy.detailSubtitle);
  const detailMetadata: Array<[string, string]> = [
    [copy.course, course.courseName], [copy.class, course.classGroup], [copy.semester, course.semesterName],
    [copy.lecturer, course.dosenName ?? '—'], [copy.assistant, course.aslabName ?? '—'], [copy.administrator, course.laboranName ?? '—'],
  ];
  detailMetadata.forEach(([label, value], index) => {
    const rowNumber = 4 + index;
    const labelCell = details.getCell(rowNumber, 1);
    labelCell.value = label;
    labelCell.font = { name: 'Aptos', size: 9, bold: true, color: { argb: 'FF047857' } };
    details.mergeCells(rowNumber, 2, rowNumber, 10);
    const valueCell = details.getCell(rowNumber, 2);
    valueCell.value = value || '—';
    valueCell.font = { name: 'Aptos', size: 9, color: { argb: 'FF172B3A' } };
    details.getRow(rowNumber).height = 22;
  });
  details.getRow(10).height = 6;
  const detailHeader = details.addRow([copy.number, copy.nim, copy.student, copy.moduleColumn, copy.moduleName, copy.submission, copy.assistantScore, copy.administratorScore, copy.moduleGrade, copy.review]);
  header(detailHeader);
  let detailIndex = 0;
  rows.forEach((student) => displayedModules.forEach((displayModule) => {
    const moduleGrade = student.modules.find((item) => item.meetingNumber === displayModule.meetingNumber);
    const submitted = Boolean(moduleGrade?.hasSubmission);
    const graded = Boolean(moduleGrade?.isCompleted && moduleGrade.hasAslabScore && moduleGrade.hasLaboranScore);
    const status = !submitted ? copy.notSubmitted : graded ? copy.complete : copy.pending;
    const row = details.addRow([
      detailIndex + 1, student.id, student.name, displayModule.meetingNumber,
      displayModule.meetingTitle || localized(copy, 'unnamedModule', displayModule.meetingNumber),
      submitted ? `✓ ${copy.yes}` : copy.no,
      moduleGrade?.aslabScore === null || moduleGrade?.aslabScore === undefined ? null : Number(moduleGrade.aslabScore),
      moduleGrade?.laboranScore === null || moduleGrade?.laboranScore === undefined ? null : Number(moduleGrade.laboranScore),
      moduleGrade?.score ?? null, status,
    ]);
    bodyRow(row, detailIndex, [7, 8, 9]);
    row.getCell(6).font = { name: 'Aptos', size: 9, bold: submitted, color: { argb: submitted ? 'FF047857' : 'FF64748B' } };
    detailIndex += 1;
  }));
  const detailLastRow = Math.max(detailHeader.number, details.rowCount);
  details.autoFilter = { from: 'A11', to: `J${detailLastRow}` };
  details.pageSetup.printTitlesRow = '1:11';
  details.pageSetup.horizontalCentered = true;

  const fileDate = new Intl.DateTimeFormat('en-CA').format(new Date());
  const slug = course.slug.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'kelas';
  return { workbook, filename: `${copy.fileName}-${slug}-${fileDate}.xlsx` };
}

export async function downloadLaprakWorkbook(rows: GradeExportRow[], course: LaprakExportCourse, locale: ExportLocale) {
  const { workbook, filename } = createLaprakWorkbook(rows, course, locale);
  const data = await workbook.xlsx.writeBuffer();
  const blob = new Blob([data as BlobPart], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = filename;
  anchor.click();
  window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}
