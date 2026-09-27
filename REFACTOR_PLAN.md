# Rencana Refactor SI Praktikum

## Tujuan

Pindahkan aplikasi Laravel 13, Blade, MySQL ke Next.js App Router, PostgreSQL Supabase, Drizzle ORM, tRPC, dan Tailwind CSS v4. Pertahankan tampilan, route, perilaku, dan akses role yang sudah ada. Tidak memasukkan seed atau data dari dump MySQL.

## Paritas fitur yang harus terjaga

- Autentikasi: login, registrasi, verifikasi email, reset/ganti password, sesi, persetujuan akun, serta ganti password saat login pertama.
- Role: `Dosen`, `Mahasiswa`, `Laboran`, dan `Aslab`; akses tiap kelas tetap mengikuti penugasan dosen/aslab/laboran dan keanggotaan mahasiswa.
- Profil dan dashboard yang menampilkan informasi sesuai role.
- Semester aktif, arsip semester/kelas, pembuatan dan pengelolaan kelas, kode pendaftaran, peserta, dan penugasan staf.
- Modul/pertemuan, publikasi modul, materi Drive, tenggat, tugas final, tutorial, dan pencarian mahasiswa.
- Presensi, rekap presensi dan unduhan laporan.
- Pengumpulan mingguan/final, riwayat versi, feedback, status ACC/revisi/ditolak, pemeriksaan berjenjang, serta berkas lama.
- Nilai praktikum, persetujuan akun, manajemen user/role, impor user, dan semua proteksi kelas arsip.
- UI, navigasi, validasi, pesan, locale Indonesia, modal, tabel, pagination, dan responsivitas mengikuti Blade yang ada.

## Arsitektur target

- Next.js App Router pada runtime Node.js untuk kompatibilitas PostgreSQL, Drizzle, dan deployment Vercel.
- TypeScript; halaman server mengambil data lewat server-side tRPC caller. Klien memakai tRPC untuk operasi interaktif.
- Drizzle ORM dengan PostgreSQL schema dan migrasi yang bersumber dari struktur Laravel migrations serta dump `si-praktikum.sql`.
- Supabase transaction pooler melalui `DATABASE_URL`; koneksi serverless dibatasi dan prepared statements dinonaktifkan untuk pooler transaction mode.
- Rahasia, URL, kredensial email, dan nilai konfigurasi deployment hanya dari environment/Supabase Vault. Tidak mengekspos kredensial database ke browser.
- Tidak ada seeder awal. Migrasi membuat struktur saja.

## Urutan kerja

1. Jadikan daftar route, view, controller, role, dan model Laravel sebagai checklist paritas.
2. Siapkan proyek Next.js, TypeScript, Tailwind CSS v4, validasi environment, Drizzle, dan tRPC.
3. Port seluruh skema aplikasi ke PostgreSQL, termasuk perubahan di migrasi terbaru dan constraint/index integritas.
4. Port autentikasi, sesi, kebijakan akses, serta validasi server.
5. Port halaman dan workflow UI dari Blade tanpa mendesain ulang.
6. Port semua aksi Laravel menjadi procedure tRPC; periksa role, kepemilikan resource, transaksi, dan invalidasi cache.
7. Tambahkan Supabase `pg_cron` + `pg_net` untuk memanggil RPC heartbeat melalui URL dan key dari Vault setiap jam pada menit 00.
8. Ganti script dan panduan Laravel dengan setup lokal, migrasi database, konfigurasi Supabase/Vercel, serta panduan impor data opsional (bukan seed).
9. Periksa kelengkapan route/role/schema, jalankan typecheck/build dan smoke check yang mungkin dilakukan tanpa kredensial produksi.

## Catatan keepalive

Job dijadwalkan dengan cron `0 * * * *` (menit 00 tiap jam, UTC sesuai scheduler Supabase). Job melakukan HTTP RPC ke project API dengan secret dari Supabase Vault agar tercatat sebagai aktivitas pengguna. Supabase mendokumentasikan bahwa Free project dapat dipause ketika aktivitas database pengguna rendah dalam 7 hari; paket berbayar meniadakan auto-pause. Keepalive terjadwal bukan pengganti jaminan paket berbayar.

## Checklist verifikasi akhir

- Tidak ada dependensi atau command runtime PHP/Laravel yang dibutuhkan aplikasi Next.js.
- Seluruh halaman/route lama dan aksi utamanya terpetakan di aplikasi baru.
- Empat role dan pembatasan per resource diuji pada sisi server.
- `DATABASE_URL` diwajibkan dari environment; tidak ada URL database atau kredensial yang ditanam di kode.
- Migrasi PostgreSQL tidak mengandung data seeder.
- Semua tampilan menggunakan Tailwind CSS v4 dan pola UI Blade sebagai acuan.
- Build produksi dan pemeriksaan runtime berhasil, atau hambatan kredensial eksternal dilaporkan secara spesifik.
