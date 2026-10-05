import ExcelJS from 'exceljs';

export async function downloadCourseRosterTemplate(locale: 'id' | 'en') {
  const workbook = new ExcelJS.Workbook();
  const roster = workbook.addWorksheet(locale === 'en' ? 'Students' : 'Mahasiswa');
  roster.columns = [
    { header: 'NIM', key: 'nim', width: 22, style: { numFmt: '@' } },
  ];
  roster.getRow(1).font = { bold: true, color: { argb: 'FF065F46' } };
  roster.getColumn(1).numFmt = '@';
  roster.views = [{ state: 'frozen', ySplit: 1 }];

  const guide = workbook.addWorksheet(locale === 'en' ? 'Instructions' : 'Petunjuk');
  guide.addRows(locale === 'en' ? [
    ['Student roster import'],
    ['Fill in the NIM column on the Students sheet.'],
    ['The student name is filled in automatically from the approved account linked to the NIM.'],
    ['Keep NIM cells formatted as text so leading zeroes are preserved.'],
  ] : [
    ['Import daftar mahasiswa'],
    ['Isi kolom NIM pada lembar Mahasiswa.'],
    ['Nama mahasiswa diambil otomatis dari akun terdaftar yang sesuai dengan NIM.'],
    ['Biarkan format kolom NIM sebagai teks agar angka nol di depan tidak hilang.'],
  ]);
  guide.getColumn(1).width = 90;
  guide.getRow(1).font = { bold: true, size: 14 };

  const data = await workbook.xlsx.writeBuffer();
  const blob = new Blob([data as BlobPart], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = locale === 'en' ? 'student-roster-template.xlsx' : 'template-daftar-mahasiswa.xlsx';
  link.click();
  window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}
