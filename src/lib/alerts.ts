'use client';

import type { SweetAlertIcon } from 'sweetalert2';
import { localeCookieName } from '@/lib/app-config';
import { getCookieLocale, type Locale } from '@/lib/locale';

function copy(locale: Locale) {
  return locale === 'en'
    ? { ok: 'Okay', continue: 'Continue', cancel: 'Cancel', save: 'Save', required: 'Please enter a name.' }
    : { ok: 'Oke', continue: 'Ya, lanjutkan', cancel: 'Batal', save: 'Simpan', required: 'Nama wajib diisi.' };
}

const englishAlertCopy: Record<string, string> = {
  'Akun akan dihapus permanen jika tidak memiliki data akademik.': 'The account can only be deleted if it has no academic records.',
  'Kelas ini akan dikembalikan ke daftar aktif.': 'This class will return to the active list.',
  'Kelas ini akan dipindahkan ke arsip.': 'This class will be moved to the archive.',
  'Nama semester': 'Semester name',
  'Semester ini beserta kelas yang terkait akan dihapus.': 'This semester and its associated classes will be deleted.',
  'Tutorial ini akan dihapus.': 'This tutorial will be deleted.',
  'Akun berhasil dihapus.': 'The account has been deleted.',
  'Role pengguna berhasil diperbarui.': 'The user role has been updated.',
  'Tidak dapat diproses': 'Could not complete the action',
  'Berhasil': 'Done',
};

function translateAlertText(value: string, locale: Locale) {
  if (locale !== 'en') return value;
  const fixed = englishAlertCopy[value];
  if (fixed) return fixed;
  const patterns: Array<[RegExp, (...parts: string[]) => string]> = [
    [/^Arsipkan kelas\?$/, () => 'Archive this class?'],
    [/^Pulihkan kelas\?$/, () => 'Restore this class?'],
    [/^Hapus akun\?$/, () => 'Delete this account?'],
    [/^Hapus semester\?$/, () => 'Delete this semester?'],
    [/^Hapus tutorial\?$/, () => 'Delete this tutorial?'],
    [/^Keluarkan mahasiswa\?$/, () => 'Remove this student?'],
    [/^Reset password\?$/, () => 'Reset password?'],
    [/^Akun (.+) akan dihapus\.$/, (name) => `The account for ${name} will be deleted.`],
    [/^(.+) akan dikeluarkan dari kelas\.$/, (name) => `${name} will be removed from this class.`],
    [/^Jabatan Aslab (.+) akan dicabut dan akun dikembalikan menjadi Mahasiswa\.$/, (name) => `Remove the lab assistant role from ${name} and return the account to Student?`],
    [/^(.+) akan diangkat menjadi (.+)\.$/, (name, role) => `${name} will be promoted to ${role === 'Laboran' ? 'Lab administrator' : role === 'Aslab' ? 'Lab assistant' : role === 'Mahasiswa' ? 'Student' : role}.`],
    [/^Ubah role menjadi (.+)\?$/, (role) => `Change role to ${role === 'Laboran' ? 'Lab administrator' : role === 'Aslab' ? 'Lab assistant' : role === 'Mahasiswa' ? 'Student' : role}?`],
    [/^Password (.+) akan direset menggunakan ID sebagai password sementara\.$/, (name) => `Reset ${name}'s password? Their user ID will become the temporary password.`],
    [/^Password (.+) berhasil direset\.$/, (name) => `${name}'s password has been reset.`],
  ];
  for (const [pattern, render] of patterns) {
    const match = value.match(pattern);
    if (match) return render(...match.slice(1));
  }
  return value;
}

export async function showAppAlert(icon: SweetAlertIcon, title: string, text: string, requestedLocale?: Locale) {
  const locale = requestedLocale ?? getCookieLocale(localeCookieName);
  const labels = copy(locale);
  const Swal = (await import('sweetalert2')).default;
  await Swal.fire({
    icon,
    title: translateAlertText(title, locale),
    text: translateAlertText(text, locale),
    confirmButtonText: labels.ok,
    confirmButtonColor: '#0f766e',
    timer: icon === 'success' ? 3200 : undefined,
    timerProgressBar: icon === 'success',
    customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg' },
  });
}

export async function confirmAppAction(title: string, text: string, icon: SweetAlertIcon = 'warning', requestedLocale?: Locale) {
  const locale = requestedLocale ?? getCookieLocale(localeCookieName);
  const labels = copy(locale);
  const Swal = (await import('sweetalert2')).default;
  const result = await Swal.fire({
    icon,
    title: translateAlertText(title, locale),
    text: translateAlertText(text, locale),
    showCancelButton: true,
    confirmButtonText: labels.continue,
    cancelButtonText: labels.cancel,
    confirmButtonColor: '#0f766e',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    focusCancel: true,
    customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg', cancelButton: 'rounded-lg' },
  });
  return result.isConfirmed;
}

export async function promptAppText(title: string, initialValue: string, requestedLocale?: Locale) {
  const locale = requestedLocale ?? getCookieLocale(localeCookieName);
  const labels = copy(locale);
  const Swal = (await import('sweetalert2')).default;
  const result = await Swal.fire({
    title: translateAlertText(title, locale),
    input: 'text',
    inputValue: initialValue,
    showCancelButton: true,
    confirmButtonText: labels.save,
    cancelButtonText: labels.cancel,
    confirmButtonColor: '#0f766e',
    cancelButtonColor: '#64748b',
    reverseButtons: true,
    inputValidator: (value) => value.trim() ? undefined : labels.required,
    customClass: { popup: 'rounded-2xl', confirmButton: 'rounded-lg', cancelButton: 'rounded-lg' },
  });
  return result.isConfirmed ? result.value.trim() as string : null;
}
