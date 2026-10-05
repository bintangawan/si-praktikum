import ExcelJS from 'exceljs';

export async function downloadStudentUserTemplate(locale: 'id' | 'en') {
  const workbook = new ExcelJS.Workbook();
  const students = workbook.addWorksheet(locale === 'en' ? 'Students' : 'Mahasiswa');
  students.columns = [
    { header: 'NIM', key: 'nim', width: 22, style: { numFmt: '@' } },
    { header: locale === 'en' ? 'Name' : 'Nama', key: 'name', width: 36 },
  ];
  students.getRow(1).font = { bold: true, color: { argb: 'FF065F46' } };
  students.getColumn(1).numFmt = '@';
  students.views = [{ state: 'frozen', ySplit: 1 }];

  const guide = workbook.addWorksheet(locale === 'en' ? 'Instructions' : 'Petunjuk');
  guide.addRows(locale === 'en' ? [
    ['Student account import'],
    ['Enter the NIM and registered student name on the Students sheet.'],
    ['New accounts and matching pending student accounts are approved by this import.'],
    ['New accounts sign in with their NIM and the same initial password, then change it at first sign-in.'],
    ['Keep NIM cells formatted as text so leading zeroes are preserved.'],
  ] : [
    ['Import akun mahasiswa'],
    ['Isi NIM dan nama mahasiswa pada lembar Mahasiswa.'],
    ['Akun baru dan akun mahasiswa pending yang datanya cocok akan disetujui oleh import ini.'],
    ['Akun baru login dengan NIM sebagai password awal, lalu wajib menggantinya saat login pertama.'],
    ['Biarkan format kolom NIM sebagai teks agar angka nol di depan tidak hilang.'],
  ]);
  guide.getColumn(1).width = 100;
  guide.getRow(1).font = { bold: true, size: 14 };

  const data = await workbook.xlsx.writeBuffer();
  const blob = new Blob([data as BlobPart], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = locale === 'en' ? 'student-account-template.xlsx' : 'template-akun-mahasiswa.xlsx';
  link.click();
  window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}
