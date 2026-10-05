import ExcelJS from 'exceljs';

type ExportLocale = 'id' | 'en';
type ExportCopy = {
  title: string;
  summary: string;
  course: string;
  class: string;
  semester: string;
  exported: string;
  lecturer: string;
  assistant: string;
  administrator: string;
  students: string;
  calculation: string;
  number: string;
  nim: string;
  student: string;
  report: string;
  reportLetter: string;
  midtermLetter: string;
  finalExamLetter: string;
  finalLetter: string;
  midterm: string;
  finalExam: string;
  finalNumeric: string;
  modulesScored: string;
  totalModules: string;
  detailTitle: string;
  detailSubtitle: string;
  moduleNumber: string;
  moduleName: string;
  assistantScore: string;
  administratorScore: string;
  moduleScore: string;
  status: string;
  completed: string;
  pending: string;
  notSubmitted: string;
  unnamedModule: (number: number) => string;
  fileName: string;
};

type ExportModule = {
  meetingNumber: number;
  meetingTitle: string | null;
  hasSubmission: boolean;
  isCompleted: boolean;
  hasAslabScore: boolean;
  hasLaboranScore: boolean;
  aslabStatus?: string;
  laboranStatus?: string;
  aslabScore: number | string | null;
  laboranScore: number | string | null;
  score: number | null;
};

export type GradeExportRow = {
  id: string;
  name: string;
  modules: ExportModule[];
  ready: boolean;
  laprak: number | null;
  laprakLetter: string | null;
  uts: number | null;
  utsLetter: string | null;
  uas: number | null;
  uasLetter: string | null;
  final: number | null;
  finalLetter: string | null;
};

export type GradeExportCourse = {
  courseName: string;
  classGroup: string;
  semesterName: string;
  dosenName?: string | null;
  aslabName?: string | null;
  laboranName?: string | null;
};

const labels: Record<ExportLocale, ExportCopy> = {
  id: {
    title: 'Rekap nilai praktikum',
    summary: 'Data nilai kelas praktikum yang diekspor dari SI Praktikum.',
    course: 'Mata praktikum',
    class: 'Kelas',
    semester: 'Semester',
    exported: 'Tanggal ekspor',
    lecturer: 'Dosen',
    assistant: 'Aslab',
    administrator: 'Laboran',
    students: 'Jumlah mahasiswa',
    calculation: 'Perhitungan: nilai modul = 80% Aslab + 20% Laboran; Laprak = rata-rata modul; nilai akhir = rata-rata Laprak, UTS, dan UAS. Nilai akhir menunggu semua modul selesai dinilai.',
    number: 'No.',
    nim: 'NIM',
    student: 'Nama mahasiswa',
    report: 'Nilai Laprak',
    reportLetter: 'Huruf Laprak',
    midterm: 'Nilai UTS',
    midtermLetter: 'Huruf UTS',
    finalExam: 'Nilai UAS',
    finalExamLetter: 'Huruf UAS',
    finalNumeric: 'Nilai akhir angka',
    finalLetter: 'Huruf akhir',
    modulesScored: 'Modul dinilai',
    totalModules: 'Jumlah modul',
    detailTitle: 'Rincian nilai per modul',
    detailSubtitle: 'Nilai Aslab, Laboran, dan nilai gabungan untuk setiap modul mahasiswa.',
    moduleNumber: 'Modul ke-',
    moduleName: 'Nama modul',
    assistantScore: 'Nilai Aslab',
    administratorScore: 'Nilai Laboran',
    moduleScore: 'Nilai modul',
    status: 'Status pemeriksaan',
    completed: 'Selesai dinilai',
    pending: 'Menunggu pemeriksaan',
    notSubmitted: 'Belum mengumpulkan',
    unnamedModule: (number: number) => `Modul ${number}`,
    fileName: 'rekap-nilai',
  },
  en: {
    title: 'Practicum grade report',
    summary: 'Practicum class grades exported from SI Praktikum.',
    course: 'Practicum course',
    class: 'Class',
    semester: 'Semester',
    exported: 'Export date',
    lecturer: 'Lecturer',
    assistant: 'Lab assistant',
    administrator: 'Lab administrator',
    students: 'Student count',
    calculation: 'Calculation: module grade = 80% assistant + 20% administrator; report grade = module average; final grade = average of report, midterm, and final exam. The final grade is available after every module is graded.',
    number: 'No.',
    nim: 'Student ID (NIM)',
    student: 'Student name',
    report: 'Report grade',
    reportLetter: 'Report letter',
    midterm: 'Midterm score',
    midtermLetter: 'Midterm letter',
    finalExam: 'Final exam score',
    finalExamLetter: 'Final exam letter',
    finalNumeric: 'Final score',
    finalLetter: 'Final letter',
    modulesScored: 'Modules graded',
    totalModules: 'Total modules',
    detailTitle: 'Module grade details',
    detailSubtitle: 'Assistant, administrator, and combined score for each student module.',
    moduleNumber: 'Module no.',
    moduleName: 'Module name',
    assistantScore: 'Assistant score',
    administratorScore: 'Administrator score',
    moduleScore: 'Module grade',
    status: 'Review status',
    completed: 'Graded',
    pending: 'Awaiting review',
    notSubmitted: 'Not submitted',
    unnamedModule: (number: number) => `Module ${number}`,
    fileName: 'grade-report',
  },
};

function valueOrBlank(value: number | null | undefined) {
  return value ?? null;
}

function setCell(cell: ExcelJS.Cell, value: ExcelJS.CellValue, bold = false) {
  cell.value = value;
  cell.font = { name: 'Aptos', size: 10, bold, color: { argb: 'FF172B3A' } };
  cell.alignment = { vertical: 'middle', wrapText: true };
}

function styleTitle(sheet: ExcelJS.Worksheet, lastColumn: string, title: string, subtitle: string) {
  sheet.mergeCells(`A1:${lastColumn}1`);
  sheet.mergeCells(`A2:${lastColumn}2`);
  const titleCell = sheet.getCell('A1');
  titleCell.value = title;
  titleCell.font = { name: 'Aptos Display', size: 18, bold: true, color: { argb: 'FFFFFFFF' } };
  titleCell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF064E3B' } };
  titleCell.alignment = { vertical: 'middle', horizontal: 'left' };
  const subtitleCell = sheet.getCell('A2');
  subtitleCell.value = subtitle;
  subtitleCell.font = { name: 'Aptos', size: 10, color: { argb: 'FF475569' }, italic: true };
  subtitleCell.alignment = { vertical: 'middle', wrapText: true };
  sheet.getRow(1).height = 34;
  sheet.getRow(2).height = 25;
}

function setMetadata(sheet: ExcelJS.Worksheet, row: number, fields: Array<[string, string]>) {
  const starts = [1, 4, 7, 10];
  fields.forEach(([label, value], index) => {
    const column = starts[index];
    const labelCell = sheet.getCell(row, column);
    labelCell.value = label;
    labelCell.font = { name: 'Aptos', size: 9, bold: true, color: { argb: 'FF047857' } };
    labelCell.alignment = { vertical: 'middle', wrapText: true };
    sheet.mergeCells(row, column + 1, row, column + 2);
    const valueCell = sheet.getCell(row, column + 1);
    valueCell.value = value || '—';
    valueCell.font = { name: 'Aptos', size: 9, color: { argb: 'FF172B3A' } };
    valueCell.alignment = { vertical: 'middle', wrapText: true };
  });
  sheet.getRow(row).height = 30;
}

function styleTableHeader(row: ExcelJS.Row) {
  row.height = 34;
  row.eachCell((cell) => {
    cell.font = { name: 'Aptos', size: 9, bold: true, color: { argb: 'FFFFFFFF' } };
    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF047857' } };
    cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
    cell.border = { bottom: { style: 'medium', color: { argb: 'FF064E3B' } } };
  });
}

function styleDataRow(row: ExcelJS.Row, index: number, numericColumns: number[], integerColumns: number[] = []) {
  row.height = 25;
  row.eachCell((cell, columnNumber) => {
    cell.font = { name: 'Aptos', size: 9, color: { argb: 'FF172B3A' } };
    cell.alignment = { vertical: 'middle', horizontal: numericColumns.includes(columnNumber) ? 'right' : 'left', wrapText: true };
    cell.border = { bottom: { style: 'hair', color: { argb: 'FFE2E8F0' } } };
    if (index % 2 === 1) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0FDF4' } };
    if (numericColumns.includes(columnNumber)) cell.numFmt = integerColumns.includes(columnNumber) ? '0' : '0.00';
  });
}

export function createGradeWorkbook(rows: GradeExportRow[], course: GradeExportCourse, locale: ExportLocale) {
  const copy = labels[locale];
  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'SI Praktikum';
  workbook.subject = `${course.courseName} · ${course.classGroup}`;
  workbook.title = copy.title;
  workbook.created = new Date();

  const summary = workbook.addWorksheet(locale === 'id' ? 'Rekap Nilai' : 'Grade Summary', {
    pageSetup: { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0 },
    views: [{ state: 'frozen', ySplit: 9 }],
  });
  summary.columns = [
    { width: 7 }, { width: 18 }, { width: 30 }, { width: 15 }, { width: 10 }, { width: 15 }, { width: 10 },
    { width: 15 }, { width: 10 }, { width: 17 }, { width: 10 }, { width: 15 }, { width: 15 },
  ];
  styleTitle(summary, 'M', copy.title, copy.summary);
  const exportedAt = new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : 'id-ID', { dateStyle: 'long' }).format(new Date());
  setMetadata(summary, 4, [[copy.course, course.courseName], [copy.class, course.classGroup], [copy.semester, course.semesterName], [copy.exported, exportedAt]]);
  setMetadata(summary, 5, [[copy.lecturer, course.dosenName ?? '—'], [copy.assistant, course.aslabName ?? '—'], [copy.administrator, course.laboranName ?? '—'], [copy.students, String(rows.length)]]);
  summary.mergeCells('A7:M7');
  setCell(summary.getCell('A7'), copy.calculation);
  summary.getCell('A7').font = { name: 'Aptos', size: 9, color: { argb: 'FF065F46' }, italic: true };
  summary.getCell('A7').fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFECFDF5' } };
  summary.getCell('A7').alignment = { vertical: 'middle', wrapText: true };
  summary.getRow(7).height = 38;
  summary.getRow(8).height = 6;
  const summaryHeaders = [copy.number, copy.nim, copy.student, copy.report, copy.reportLetter, copy.midterm, copy.midtermLetter, copy.finalExam, copy.finalExamLetter, copy.finalNumeric, copy.finalLetter, copy.modulesScored, copy.totalModules];
  const header = summary.addRow(summaryHeaders);
  styleTableHeader(header);
  rows.forEach((student, index) => {
    const modulesScored = student.modules.filter((module) => module.isCompleted && module.hasAslabScore && module.hasLaboranScore).length;
    const dataRow = summary.addRow([
      index + 1, student.id, student.name, valueOrBlank(student.laprak), student.laprakLetter,
      valueOrBlank(student.uts), student.utsLetter, valueOrBlank(student.uas), student.uasLetter,
      valueOrBlank(student.final), student.finalLetter, modulesScored, student.modules.length,
    ]);
    styleDataRow(dataRow, index, [4, 6, 8, 10, 12, 13], [12, 13]);
  });
  const summaryLastRow = Math.max(header.number, summary.rowCount);
  summary.autoFilter = { from: 'A9', to: `M${summaryLastRow}` };
  summary.pageSetup.printTitlesRow = '1:9';
  summary.pageSetup.horizontalCentered = true;
  summary.eachRow((row) => row.eachCell((cell) => { cell.protection = { locked: true }; }));

  const details = workbook.addWorksheet(locale === 'id' ? 'Rincian Modul' : 'Module Details', {
    pageSetup: { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0 },
    views: [{ state: 'frozen', ySplit: 8 }],
  });
  details.columns = [{ width: 7 }, { width: 18 }, { width: 30 }, { width: 13 }, { width: 34 }, { width: 17 }, { width: 18 }, { width: 17 }, { width: 24 }];
  styleTitle(details, 'I', copy.detailTitle, copy.detailSubtitle);
  const metadataFields: Array<[string, string]> = [
    [copy.course, course.courseName], [copy.class, course.classGroup], [copy.semester, course.semesterName],
    [copy.lecturer, course.dosenName ?? '—'], [copy.assistant, course.aslabName ?? '—'], [copy.administrator, course.laboranName ?? '—'],
  ];
  metadataFields.forEach(([label, value], index) => {
    const rowNumber = 4 + index;
    const labelCell = details.getCell(rowNumber, 1);
    labelCell.value = label;
    labelCell.font = { name: 'Aptos', size: 9, bold: true, color: { argb: 'FF047857' } };
    details.mergeCells(rowNumber, 2, rowNumber, 9);
    const valueCell = details.getCell(rowNumber, 2);
    valueCell.value = value || '—';
    valueCell.font = { name: 'Aptos', size: 9, color: { argb: 'FF172B3A' } };
    details.getRow(rowNumber).height = 22;
  });
  details.getRow(10).height = 6;
  const detailHeaders = [copy.number, copy.nim, copy.student, copy.moduleNumber, copy.moduleName, copy.assistantScore, copy.administratorScore, copy.moduleScore, copy.status];
  const detailHeader = details.addRow(detailHeaders);
  styleTableHeader(detailHeader);
  let detailIndex = 0;
  rows.forEach((student) => student.modules.forEach((module) => {
    const status = module.isCompleted && module.hasAslabScore && module.hasLaboranScore
      ? copy.completed
      : module.hasSubmission ? copy.pending : copy.notSubmitted;
    const title = module.meetingTitle || copy.unnamedModule(module.meetingNumber);
    const dataRow = details.addRow([
      detailIndex + 1, student.id, student.name, module.meetingNumber, title,
      module.aslabScore === null ? null : Number(module.aslabScore),
      module.laboranScore === null ? null : Number(module.laboranScore),
      valueOrBlank(module.score), status,
    ]);
    styleDataRow(dataRow, detailIndex, [6, 7, 8]);
    detailIndex += 1;
  }));
  const detailsLastRow = Math.max(detailHeader.number, details.rowCount);
  details.autoFilter = { from: 'A11', to: `I${detailsLastRow}` };
  details.pageSetup.printTitlesRow = '1:11';
  details.pageSetup.horizontalCentered = true;
  details.eachRow((row) => row.eachCell((cell) => { cell.protection = { locked: true }; }));

  const filenameDate = new Intl.DateTimeFormat('en-CA').format(new Date());
  const slug = course.courseName.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'kelas';
  return { workbook, filename: `${copy.fileName}-${slug}-${filenameDate}.xlsx` };
}

export async function downloadGradeWorkbook(rows: GradeExportRow[], course: GradeExportCourse, locale: ExportLocale) {
  const { workbook, filename } = createGradeWorkbook(rows, course, locale);
  const data = await workbook.xlsx.writeBuffer();
  const blob = new Blob([data as BlobPart], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = filename;
  anchor.click();
  window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}
