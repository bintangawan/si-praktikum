import assert from 'node:assert/strict';
import test from 'node:test';
import ExcelJS from 'exceljs';
import { createLaprakWorkbook } from '../src/lib/laprak-export';
import type { GradeExportCourse, GradeExportRow } from '../src/lib/grade-export';

const course: GradeExportCourse & { slug: string; meetings: Array<{ meetingNumber: number }> } = {
  slug: 'pembelajaran-mesin-ik-1',
  courseName: 'Pembelajaran Mesin',
  classGroup: 'IK-1',
  semesterName: 'Ganjil 2026/2027',
  dosenName: 'Dosen Uji',
  aslabName: 'Aslab Uji',
  laboranName: 'Laboran Uji',
  meetings: [{ meetingNumber: 1 }, { meetingNumber: 2 }],
};

const rows: GradeExportRow[] = [{
  id: '23000001',
  name: 'Mahasiswa Uji',
  modules: [
    { meetingNumber: 1, meetingTitle: 'TF IDF', hasSubmission: true, isCompleted: true,
      hasAslabScore: true, hasLaboranScore: true, aslabScore: '90.00', laboranScore: '80.00', score: 88 },
    { meetingNumber: 2, meetingTitle: 'Klasifikasi', hasSubmission: false, isCompleted: false,
      hasAslabScore: false, hasLaboranScore: false, aslabScore: null, laboranScore: null, score: null },
  ],
  ready: false,
  laprak: null,
  laprakLetter: null,
  uts: null,
  utsLetter: null,
  uas: null,
  uasLetter: null,
  final: null,
  finalLetter: null,
}];

test('Laprak Excel includes automatic submission checks, grade details, and styling', async () => {
  const { workbook, filename } = createLaprakWorkbook(rows, course, 'id');
  assert.match(filename, /^rekap-laprak-pembelajaran-mesin-ik-1-\d{4}-\d{2}-\d{2}\.xlsx$/);
  assert.deepEqual(workbook.worksheets.map((sheet) => sheet.name), ['Rekap Laprak', 'Rincian Modul']);

  const summary = workbook.getWorksheet('Rekap Laprak');
  assert.ok(summary);
  assert.equal(summary.getCell('D10').value, '✓\n88.00');
  assert.equal(summary.getCell('E10').value, '—');
  assert.equal(summary.getCell('F10').value, '1/2');
  assert.equal(summary.getCell('G10').value, '1/2');
  assert.equal(summary.getCell('H10').value, null);
  assert.equal(summary.getCell('D10').fill.fgColor?.argb, 'FFECFDF5');
  assert.equal(summary.views[0]?.state, 'frozen');
  assert.equal(summary.views[0]?.ySplit, 9);

  const details = workbook.getWorksheet('Rincian Modul');
  assert.ok(details);
  assert.equal(details.getCell('F12').value, '✓ Sudah masuk');
  assert.equal(details.getCell('G12').value, 90);
  assert.equal(details.getCell('H12').value, 80);
  assert.equal(details.getCell('I12').value, 88);
  assert.equal(details.getCell('J12').value, 'Selesai dinilai');
  assert.equal(details.getCell('F13').value, 'Belum masuk');
  assert.equal(details.getCell('J13').value, 'Belum mengumpulkan');

  const buffer = await workbook.xlsx.writeBuffer();
  assert.ok(buffer.byteLength > 0);
  const restored = await new ExcelJS.Workbook().xlsx.load(buffer);
  assert.equal(restored.getWorksheet('Rekap Laprak')?.getCell('D10').value, '✓\n88.00');
  assert.equal(restored.getWorksheet('Rincian Modul')?.getCell('G12').value, 90);
});

test('Laprak Excel remains valid when a class has no modules or participants', async () => {
  const { workbook } = createLaprakWorkbook([], { ...course, meetings: [] }, 'en');
  const summary = workbook.getWorksheet('Laprak Summary');
  assert.ok(summary);
  assert.equal(summary.getRow(9).cellCount, 7);
  assert.equal(summary.getCell('A9').value, 'No.');
  assert.equal(summary.getCell('L1').isMerged, true);
  assert.equal(summary.getCell('J4').value, 'Export date');
  const buffer = await workbook.xlsx.writeBuffer();
  assert.ok(buffer.byteLength > 0);

  const noRoster = createLaprakWorkbook([], { ...course, meetings: [{ meetingNumber: 1, title: 'Named module' }] }, 'en').workbook;
  assert.equal(noRoster.getWorksheet('Laprak Summary')?.getCell('D9').value, 'Module no. 1 Named module');
});

test('a complete 7 of 7 submission set exports checkmarks for every module', () => {
  const sevenModules = Array.from({ length: 7 }, (_, index) => ({
    meetingNumber: index + 1,
    meetingTitle: `Modul ${index + 1}`,
    hasSubmission: true,
    isCompleted: true,
    hasAslabScore: true,
    hasLaboranScore: true,
    aslabScore: 80,
    laboranScore: 80,
    score: 80,
  }));
  const sevenModuleCourse = { ...course, meetings: sevenModules.map(({ meetingNumber }) => ({ meetingNumber })) };
  const completeStudent = { ...rows[0], modules: sevenModules, ready: true, laprak: 80, laprakLetter: 'B' };
  const { workbook } = createLaprakWorkbook([completeStudent], sevenModuleCourse, 'id');
  const summary = workbook.getWorksheet('Rekap Laprak');
  assert.ok(summary);
  for (let column = 4; column <= 10; column += 1) assert.match(String(summary.getCell(10, column).value), /^✓\n80\.00$/);
  assert.equal(summary.getCell('K10').value, '7/7');
  assert.equal(summary.getCell('L10').value, '7/7');
  assert.equal(summary.getCell('M10').value, 80);
});
