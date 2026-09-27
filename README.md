# SI Praktikum

SI Praktikum mengelola akun dan persetujuannya, kelas dan semester, modul praktikum, presensi, pengumpulan laporan, pemeriksaan berjenjang, nilai, arsip, tutorial, dan import akun. Aplikasi ini telah dipindahkan ke Next.js, Supabase PostgreSQL, Drizzle ORM, tRPC, dan Tailwind CSS v4. Tampilan menggunakan App Router; query halaman berjalan langsung di server tanpa request HTTP internal.

## Teknologi

- Next.js 16 App Router dan React 19
- TypeScript, tRPC 11, TanStack Query
- Supabase PostgreSQL dan private Storage
- Drizzle ORM dan Drizzle Kit
- Tailwind CSS v4
- Node.js 22 LTS direkomendasikan; fungsi Vercel diarahkan ke Singapore (`sin1`) agar dekat dengan Supabase `ap-southeast-1`.

## Menjalankan lokal

```powershell
Copy-Item .env.example .env.local
npm ci
```

Isi `.env.local` dari Dashboard Supabase. Jangan commit `.env.local` atau masukkan password/key ke source code. Gunakan URI **Transaction pooler** dari menu Connect Supabase pada `DATABASE_URL`; driver Postgres sudah disetel `prepare: false` dan ukuran pool kecil untuk lingkungan serverless.

Perintah Drizzle juga memuat `.env.local` melalui `@next/env`, jadi konfigurasi database lokal sama dengan runtime Next.js. Untuk runtime Vercel, gunakan Transaction pooler pada `DATABASE_URL`; bila menjalankan migrasi dari lingkungan yang mendukung direct connection atau session pooler, isi `DATABASE_MIGRATION_URL`.

Jalankan migrasi skema PostgreSQL dan server:

```powershell
npm run db:setup
npm run dev
```

Buka `http://localhost:3000`. Jalankan pemeriksaan sebelum deploy:

```powershell
npm run typecheck
npm run build
```

Dump Laravel lama dapat diimpor satu kali ke database Supabase memakai Drizzle. Import membawa data aplikasi dan keempat role (`Dosen`, `Mahasiswa`, `Laboran`, `Aslab`); tidak ada data contoh tambahan. File dump berisi data pribadi dan hash password sehingga disimpan lokal serta diabaikan Git.

## Environment

Lihat [.env.example](.env.example). Nilai penting:

- `DATABASE_URL`: URI Transaction pooler Supabase dari dashboard. Jangan memakai URI MySQL.
- `DATABASE_MIGRATION_URL` (opsional): koneksi khusus `drizzle-kit` melalui direct connection atau session pooler. Jika kosong, migrasi menggunakan `DATABASE_URL`.
- `APP_URL`: origin publik aplikasi, misalnya origin lokal atau domain Vercel.
- `NEXT_PUBLIC_APP_NAME`: nama aplikasi yang tampil di halaman dan metadata; nilai ini bersifat publik.
- `APP_DESCRIPTION`: deskripsi metadata aplikasi.
- `NEXT_PUBLIC_DEFAULT_LOCALE`, `NEXT_PUBLIC_LOCALE_COOKIE_NAME`, `LOCALE_COOKIE_TTL_DAYS`: bahasa awal dan pengaturan cookie bahasa; nama cookie bersifat publik.
- `SESSION_COOKIE_NAME`, `SESSION_TTL_DAYS`, `BCRYPT_ROUNDS`: pengaturan sesi dan hash password.
- `DB_POOL_MAX`, `DB_CONNECT_TIMEOUT`, `DB_IDLE_TIMEOUT`: ukuran dan batas waktu koneksi Postgres.
- `SUPABASE_URL`, `SUPABASE_SECRET_KEY`, `SUPABASE_STORAGE_BUCKET`: akses server untuk file privat. URL dan secret key tersedia di Supabase Dashboard > Project Settings > API Keys. Secret key hanya boleh berada di environment server.

Konfigurasi wajib divalidasi tanpa nilai fallback di source. `NODE_ENV` disediakan oleh Next.js/Vercel. Variabel `NEXT_PUBLIC_*` dimasukkan ke bundle browser saat build; perubahan nilainya di Vercel memerlukan redeploy. Kredensial database dan secret key server tidak memakai prefix tersebut.

`npm run db:migrate` membuat skema PostgreSQL aplikasi saja; bucket Storage adalah resource terpisah sehingga tidak dibuat oleh Drizzle. `npm run db:storage` membuat bucket privat sesuai `SUPABASE_STORAGE_BUCKET` melalui Supabase Storage API, atau memastikan bucket yang sudah ada tetap private. Perintah ini idempoten dan membutuhkan secret key server (`sb_secret_...`), bukan publishable key. Untuk menyiapkan keduanya sekaligus pada environment baru, jalankan `npm run db:setup`. Registrasi tidak meminta verifikasi email. Laboran atau Aslab menyetujui akun mahasiswa; permintaan lupa password ditangani langsung oleh pengelola setelah memeriksa identitas. Pengumpulan laporan mengikuti perilaku Laravel dan menggunakan link Google Drive tervalidasi. Untuk file lama yang memiliki `file_path`, salin objeknya ke bucket tersebut dengan path yang sama agar endpoint file privat dapat membacanya.

## Database dan migrasi

File [drizzle/0000_workable_wildside.sql](drizzle/0000_workable_wildside.sql) adalah migrasi PostgreSQL yang dibangkitkan dari skema aplikasi Laravel. File itu membuat tabel aplikasi, enum status/role, foreign key, index, dan mengaktifkan Row Level Security pada tabel publik. Tidak ada policy untuk `anon`/`authenticated`; aplikasi mengakses data dari server melalui kredensial database, lalu memeriksa sesi, persetujuan, role, kepemilikan, dan kelas di tRPC.

Buat perubahan skema melalui `src/server/db/schema.ts`, kemudian jalankan `npm run db:generate` untuk membuat migrasi baru. Jalankan migrasi di lingkungan target dengan `npm run db:migrate`. File `si-praktikum.sql` di root adalah dump MariaDB/MySQL lama, bukan SQL yang dapat dijalankan langsung di Supabase. Importer satu kali mengonversi nilai ke skema Drizzle, menjaga ID/relasi/hash password, mengabaikan tabel infrastruktur Laravel, dan mengatur ulang sequence PostgreSQL.

Setelah dump tersedia secara lokal dan migrasi skema sudah diterapkan pada Supabase target, jalankan pemeriksaan tanpa menulis ke database lalu lakukan import satu kali:

```powershell
npm run db:import-legacy:check
npm run db:import-legacy:apply
npm run db:import-legacy:verify
```

Mode apply berjalan dalam satu transaksi, mengunci tabel aplikasi, dan membatalkan seluruh perubahan bila tabel aplikasi sudah berisi data. Verifikasi membaca jumlah row hasil impor tanpa menampilkan data pengguna. Jalankan sebelum aplikasi production menerima traffic. Jangan jalankan ulang saat deploy. Pastikan file `si-praktikum.sql` tidak di-commit; berkas ini sudah tercakup dalam `.gitignore`. Baris avatar lama hanya membawa path dari dump; objek gambar harus disalin terpisah ke private Storage dengan path yang sama bila file sumbernya tersedia.

## Keepalive Supabase setiap hari

Jadwal di environment adalah `SUPABASE_KEEPALIVE_CRON_UTC="0 17 * * *"`: setiap hari pukul **00:00 WIB (17:00 UTC)**. Job Supabase memakai pg_cron dan pg_net untuk memanggil Data API dan menulis satu baris heartbeat. Konfigurasi berasal dari environment dan disimpan ke Supabase Vault oleh script setup.

Isi password pada DATABASE_URL dan DATABASE_MIGRATION_URL, lalu jalankan:

```bash
npm run db:keepalive:check
npm run db:keepalive
```

Opsi `--check` hanya memeriksa format environment dan ketersediaan file SQL tanpa koneksi database. Perintah kedua memasang [migrasi keepalive](supabase/migrations/20260925000000_daily_keepalive.sql) dan mengganti job lama secara transaksional. Periksa `cron.job`, `cron.job_run_details`, serta `public.app_heartbeats` setelah jadwal berjalan. Setup terpisah dari `npm run db:migrate`.

`SUPABASE_PUBLISHABLE_KEY` dikirim melalui header `apikey`, sesuai [dokumentasi API keys](https://supabase.com/docs/guides/getting-started/api-keys). Key ini hanya digunakan oleh cron keepalive; operasi Storage privat di aplikasi server menggunakan `SUPABASE_SECRET_KEY`.

Koneksi direct membutuhkan IPv6; bila jaringan hanya mendukung IPv4, isi `DATABASE_MIGRATION_URL` dengan Session pooler dari Dashboard Supabase > Connect. Jadwal berjalan di Supabase tanpa bergantung pada Vercel. Job tidak dapat berjalan ketika database sudah paused. Heartbeat tidak menjamin pengecualian dari [kebijakan pause Free plan](https://supabase.com/docs/guides/platform/free-project-pausing).

## Vercel

1. Import repository ke Vercel dan pilih framework Next.js. `vercel.json` menempatkan fungsi server di `sin1`, dekat dengan database Supabase Singapore.
2. Tambahkan nilai environment yang dibutuhkan dari `.env.example` pada pengaturan project Vercel. Gunakan domain production untuk `APP_URL` dan URI Transaction pooler untuk `DATABASE_URL`; `NODE_ENV` ditentukan otomatis oleh Vercel.
3. Jalankan `npm run db:setup` sekali dari terminal dengan environment production yang terhubung ke database target. Perintah ini menjalankan migrasi Drizzle lalu memastikan bucket Storage privat tersedia.
4. Jalankan `npm run db:keepalive` satu kali dengan environment database target.
5. Deploy dengan build command `npm run build`.

Jangan menambahkan password atau `SUPABASE_SECRET_KEY` ke `NEXT_PUBLIC_*`. `SUPABASE_SECRET_KEY` hanya digunakan di server untuk operasi Storage.

## Role dan alur

- **Mahasiswa:** registrasi, persetujuan akun, enrollment kelas, presensi, pengumpulan link laporan, riwayat versi, pengumpulan final, serta kartu praktikum.
- **Aslab:** kelas yang ditugaskan, modul, presensi, pemeriksaan tahap pertama, nilai modul, dan persetujuan akun.
- **Laboran:** semester, kelas, penugasan staf, peserta, modul, tutorial, pengguna, import akun, arsip, dan pemeriksaan tahap kedua.
- **Dosen:** kelas yang diampu, monitoring, pemeriksaan laporan final setelah Aslab dan Laboran, dan pengelolaan nilai akhir.

Server selalu membaca role dari database. Aksi penulisan pada kelas arsip ditolak; link file dan endpoint unduhan memeriksa sesi serta hak akses sebelum mengirim file.
