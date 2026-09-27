import type { Locale } from './locale';

type Copy = { title: string; text: string };

const messages: Record<string, Record<Locale, Copy>> = {
  'auth.updateProfile': { id: { title: 'Profil diperbarui', text: 'Informasi profil berhasil disimpan.' }, en: { title: 'Profile updated', text: 'Your profile information has been saved.' } },
  'auth.updatePassword': { id: { title: 'Password diperbarui', text: 'Password baru berhasil disimpan.' }, en: { title: 'Password updated', text: 'Your new password has been saved.' } },
  'courses.create': { id: { title: 'Kelas dibuat', text: 'Kelas praktikum berhasil dibuat.' }, en: { title: 'Class created', text: 'The practicum class was created.' } },
  'courses.update': { id: { title: 'Kelas diperbarui', text: 'Perubahan kelas berhasil disimpan.' }, en: { title: 'Class updated', text: 'Class changes have been saved.' } },
  'courses.delete': { id: { title: 'Kelas dihapus', text: 'Kelas berhasil dihapus.' }, en: { title: 'Class deleted', text: 'The class was deleted.' } },
  'courses.archive': { id: { title: 'Status kelas diperbarui', text: 'Status arsip kelas berhasil diubah.' }, en: { title: 'Class status updated', text: 'The class archive status has changed.' } },
  'courses.enroll': { id: { title: 'Berhasil bergabung', text: 'Anda telah bergabung ke kelas.' }, en: { title: 'Class joined', text: 'You have joined the class.' } },
  'courses.addStudent': { id: { title: 'Peserta ditambahkan', text: 'Mahasiswa berhasil ditambahkan ke kelas.' }, en: { title: 'Student added', text: 'The student has been added to the class.' } },
  'courses.removeStudent': { id: { title: 'Peserta dikeluarkan', text: 'Mahasiswa berhasil dikeluarkan dari kelas.' }, en: { title: 'Student removed', text: 'The student has been removed from the class.' } },
  'courses.updateStaff': { id: { title: 'Penugasan diperbarui', text: 'Staf kelas berhasil diperbarui.' }, en: { title: 'Staff assignment updated', text: 'Class staff assignments have been updated.' } },
  'courses.updateModules': { id: { title: 'Modul diperbarui', text: 'Pengaturan modul berhasil disimpan.' }, en: { title: 'Modules updated', text: 'Module settings have been saved.' } },
  'courses.updateGrade': { id: { title: 'Nilai tersimpan', text: 'Nilai mahasiswa berhasil diperbarui.' }, en: { title: 'Grades saved', text: 'The student grades have been updated.' } },
  'attendance.save': { id: { title: 'Presensi tersimpan', text: 'Perubahan presensi berhasil disimpan.' }, en: { title: 'Attendance saved', text: 'Attendance changes have been saved.' } },
  'meetings.update': { id: { title: 'Modul diperbarui', text: 'Materi modul berhasil disimpan.' }, en: { title: 'Module updated', text: 'Module materials have been saved.' } },
  'meetings.setDeadline': { id: { title: 'Tenggat diperbarui', text: 'Batas waktu pengumpulan berhasil disimpan.' }, en: { title: 'Deadline updated', text: 'The submission deadline has been saved.' } },
  'meetings.finalTaskUpdate': { id: { title: 'Tugas akhir diperbarui', text: 'Pengaturan tugas akhir berhasil disimpan.' }, en: { title: 'Final task updated', text: 'Final task settings have been saved.' } },
  'meetings.createFinalTask': { id: { title: 'Tugas akhir dibuat', text: 'Tugas akhir kelas berhasil dibuat.' }, en: { title: 'Final task created', text: 'The class final task has been created.' } },
  'semesters.create': { id: { title: 'Semester ditambahkan', text: 'Semester baru berhasil dibuat.' }, en: { title: 'Semester added', text: 'The new semester has been created.' } },
  'semesters.update': { id: { title: 'Semester diperbarui', text: 'Perubahan semester berhasil disimpan.' }, en: { title: 'Semester updated', text: 'Semester changes have been saved.' } },
  'semesters.setActive': { id: { title: 'Semester aktif diperbarui', text: 'Semester aktif berhasil diubah.' }, en: { title: 'Active semester updated', text: 'The active semester has changed.' } },
  'semesters.delete': { id: { title: 'Semester dihapus', text: 'Semester berhasil dihapus.' }, en: { title: 'Semester deleted', text: 'The semester was deleted.' } },
  'users.approve': { id: { title: 'Akun disetujui', text: 'Akun mahasiswa berhasil disetujui.' }, en: { title: 'Account approved', text: 'The student account has been approved.' } },
  'users.approveAll': { id: { title: 'Akun disetujui', text: 'Semua akun yang menunggu berhasil disetujui.' }, en: { title: 'Accounts approved', text: 'All pending accounts have been approved.' } },
  'users.deletePending': { id: { title: 'Akun dihapus', text: 'Akun yang menunggu berhasil dihapus.' }, en: { title: 'Account deleted', text: 'The pending account was deleted.' } },
  'users.importUsers': { id: { title: 'Pengguna diimpor', text: 'Data pengguna berhasil diproses.' }, en: { title: 'Users imported', text: 'User records have been processed.' } },
  'submissions.submit': { id: { title: 'Laporan dikirim', text: 'Laporan praktikum berhasil dikirim.' }, en: { title: 'Report submitted', text: 'The practicum report has been submitted.' } },
  'submissions.submitFinal': { id: { title: 'Laporan final dikirim', text: 'Laporan final berhasil dikirim.' }, en: { title: 'Final report submitted', text: 'The final report has been submitted.' } },
  'submissions.revise': { id: { title: 'Revisi dikirim', text: 'Perbaikan laporan berhasil dikirim.' }, en: { title: 'Revision submitted', text: 'The revised report has been submitted.' } },
  'submissions.review': { id: { title: 'Pemeriksaan tersimpan', text: 'Status pemeriksaan laporan berhasil diperbarui.' }, en: { title: 'Review saved', text: 'The report review status has been updated.' } },
  'submissions.setDeadline': { id: { title: 'Tenggat diperbarui', text: 'Batas waktu laporan berhasil disimpan.' }, en: { title: 'Deadline updated', text: 'The report deadline has been saved.' } },
  'submissions.setScore': { id: { title: 'Nilai tersimpan', text: 'Nilai laporan berhasil disimpan.' }, en: { title: 'Score saved', text: 'The report score has been saved.' } },
  'submissions.updateFinalTask': { id: { title: 'Tugas akhir diperbarui', text: 'Pengaturan tugas akhir berhasil disimpan.' }, en: { title: 'Final task updated', text: 'Final task settings have been saved.' } },
  'tutorials.create': { id: { title: 'Tutorial ditambahkan', text: 'Materi tutorial berhasil ditambahkan.' }, en: { title: 'Tutorial added', text: 'The tutorial material has been added.' } },
  'tutorials.delete': { id: { title: 'Tutorial dihapus', text: 'Materi tutorial berhasil dihapus.' }, en: { title: 'Tutorial deleted', text: 'The tutorial material was deleted.' } },
};

export function getCrudAlertCopy(procedure: string, locale: Locale): Copy | undefined {
  return messages[procedure]?.[locale];
}

export function getMutationProcedure(mutationKey: readonly unknown[] | undefined): string {
  const path = mutationKey?.[0];
  return Array.isArray(path) ? path.join('.') : '';
}
