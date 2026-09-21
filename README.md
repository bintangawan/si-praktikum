# SI Praktikum

SI Praktikum adalah sistem informasi pengelolaan kegiatan praktikum berbasis Laravel. Aplikasi menangani pendaftaran dan persetujuan akun, semester, kelas, modul, peserta, presensi, pengumpulan laprak, pemeriksaan berjenjang, arsip, tutorial, serta ekspor laporan PDF dan Excel.

## Fitur utama

- Pendaftaran mahasiswa dengan persetujuan akun oleh Laboran atau Aslab.
- Import akun dan kewajiban mengganti password pada login pertama.
- Manajemen semester aktif dan arsip kelas yang hanya dapat dibaca.
- Pembuatan kelas dengan jumlah modul yang fleksibel dari 1 sampai 16.
- Preview struktur modul sebelum kelas dibuat.
- Kartu modul `Coming Soon` sampai pengumpulan dibuka oleh Aslab atau Laboran.
- Penambahan modul hingga maksimal 16 serta pengelolaan judul, status pengumpulan, link materi opsional, instruksi, dan deadline.
- Enrollment mahasiswa menggunakan kode kelas.
- Pencarian dan pengelolaan peserta kelas.
- Presensi per pertemuan serta rekap PDF dan Excel.
- Pengumpulan laprak melalui link file Google Drive.
- Riwayat versi dokumen dan pemeriksaan laprak.
- Status laporan: Pending, Revisi, Ditolak, dan ACC.
- Pemeriksaan berjenjang oleh Aslab, Laboran, dan Dosen.
- Notifikasi serta konfirmasi aksi CRUD menggunakan SweetAlert2.
- Tampilan responsif berbasis Blade, Tailwind CSS, dan Alpine.js.

## Alur pengguna

### Mahasiswa

1. Mahasiswa mendaftar menggunakan NIM, nama, email, dan password.
2. Akun menunggu persetujuan Laboran atau Aslab.
3. Setelah disetujui, mahasiswa dapat masuk dan bergabung ke kelas menggunakan kode enrollment.
4. Modul dengan pengumpulan yang belum dibuka tampil sebagai `Coming Soon` dan belum dapat menerima laprak.
5. Modul aktif dapat menerima link laprak meskipun link materi belum tersedia. Mahasiswa juga dapat melihat deadline dan status presensi.
6. Laporan berstatus Revisi atau Ditolak dapat dikirim ulang sebagai versi baru. Riwayat versi sebelumnya tetap tersimpan.

### Aslab

- Mengakses kelas tempat dirinya ditugaskan.
- Menambah dan mengatur judul modul, membuka pengumpulan, serta melengkapi materi, instruksi, dan deadline bila sudah tersedia.
- Mengelola presensi dan peserta bersama petugas yang berwenang.
- Melakukan pemeriksaan laprak tahap pertama.
- Memverifikasi akun mahasiswa yang masih menunggu.

### Laboran

- Mengelola semester, pengguna, tutorial, kelas, peserta, dan penugasan petugas.
- Menentukan jumlah modul ketika kelas dibuat. Jumlah modul dapat berbeda untuk setiap kelas, dengan batas 1–16.
- Mengelola modul dan status pengumpulan sebagai administrator bila diperlukan.
- Melakukan pemeriksaan laprak tahap kedua.
- Mengelola persetujuan akun mahasiswa.

### Dosen

- Mengakses kelas yang diampu.
- Memantau peserta, presensi, dan laporan.
- Melakukan pemeriksaan akhir untuk laporan final setelah disetujui Aslab dan Laboran.

## Struktur modul

Jumlah modul awal ditetapkan oleh Laboran saat membuat kelas. Sistem langsung membuat kartu Modul 1 sampai Modul N menggunakan identitas database yang tetap. Aslab atau Laboran dapat menambah modul kemudian hingga total 16. Nomor modul tidak dipakai sebagai identitas penyimpanan sehingga perubahan judul tidak dapat memindahkan presensi atau laprak ke modul lain.

Modul awal berstatus draft dan tampil sebagai `Coming Soon`. Aslab atau Laboran membuka pengumpulan melalui pengaturan modul. Link materi Google Drive bersifat opsional, dapat ditambahkan kemudian, dan tidak menentukan apakah mahasiswa dapat mengumpulkan laprak. Halaman monitoring menampilkan link Drive setiap mahasiswa, preview dokumen, dan akses ke proses review.

## Alur pemeriksaan laporan

Laprak mingguan diperiksa secara berurutan:

```text
Mahasiswa → Aslab → Laboran → Selesai
```

Laporan final diperiksa secara berurutan:

```text
Mahasiswa → Aslab → Laboran → Dosen → Selesai
```

Pemeriksa dapat memilih ACC, Revisi, atau Ditolak. Revisi dan penolakan wajib memiliki feedback. Ketika mahasiswa mengirim versi perbaikan, status pemeriksaan kembali ke tahap awal dan persetujuan versi sebelumnya tidak diwariskan.

## Teknologi

- PHP 8.3+
- Laravel 13
- MySQL 8+ atau MariaDB yang kompatibel
- Blade dan Alpine.js
- Tailwind CSS 3
- Vite 8
- SweetAlert2
- DomPDF
- Laravel Excel

## Persyaratan sistem

- PHP 8.3 atau lebih baru dengan ekstensi PDO MySQL, mbstring, intl, GD, ZIP, dan XML.
- MySQL 8+ atau MariaDB yang kompatibel.
- Composer 2.
- Node.js dan npm yang kompatibel dengan Vite 8.

## Instalasi lokal

Pasang dependency dan buat file environment:

```bash
composer install
npm install
```

Windows:

```powershell
Copy-Item .env.example .env
```

Linux atau macOS:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Buat database MySQL, lalu sesuaikan konfigurasi berikut di `.env`:

```dotenv
APP_NAME="SI Praktikum"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=id
APP_FALLBACK_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=si-praktikum
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi, seeder, dan build aset:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
```

Jalankan aplikasi lokal:

```bash
composer run dev
```

Aplikasi tersedia di `http://localhost:8000`.

### Akun awal

`php artisan migrate:fresh --seed` hanya membuat tiga akun berikut tanpa semester, kelas, atau data akademik contoh:

| Peran | Nama | ID | Email |
| --- | --- | --- | --- |
| Laboran | Muhammad Fathir Aulia | `0701223160` | `laboran@uinsu.ac.id` |
| Aslab | Bintang Kurniawan Herman | `0701222090` | `aslab@uinsu.ac.id` |
| Mahasiswa | Bintangin | `0701225090` | `0701225090@student.uinsu.ac.id` |

Password awal ketiga akun adalah `password`. Ganti password sebelum menggunakan akun tersebut di luar lingkungan pengembangan.

## Penyimpanan dokumen

Pengumpulan baru menggunakan link file Google Drive dan tidak menyimpan salinan PDF baru di server aplikasi. Link harus:

- Menggunakan HTTPS.
- Mengarah ke `drive.google.com`.
- Berupa link file, bukan folder.
- Memiliki akses baca untuk pengguna yang perlu memeriksa dokumen.

Endpoint file lokal tetap tersedia untuk membaca dokumen lama yang telah tersimpan sebelum perpindahan ke alur link Google Drive.

## Pengujian dan pemeriksaan kualitas

Jalankan seluruh test backend:

```bash
php artisan test
```

Jalankan test JavaScript:

```bash
node --test tests/js/workflow.test.mjs
```

Periksa format PHP, kompilasi Blade, dan build production:

```bash
vendor/bin/pint --test
php artisan view:cache
php artisan view:clear
npm run build
```

Test Laravel menggunakan SQLite in-memory sehingga tidak mengubah database development.

## Deployment production

Gunakan konfigurasi minimum berikut:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-aplikasi.example
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
```

Ketentuan deployment:

- Gunakan HTTPS dan arahkan document root web server ke direktori `public`.
- Gunakan akun database khusus aplikasi dengan password kuat.
- Simpan `APP_KEY` production dengan aman dan jangan menggantinya saat update biasa.
- Jangan commit atau menyalin `.env` dari development ke production.
- Konfigurasikan mailer production jika fitur reset password melalui email digunakan.
- Jalankan queue worker di bawah process manager jika `QUEUE_CONNECTION` menggunakan driver asynchronous.

Urutan rilis yang direkomendasikan:

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan up
```

Migration `add_published_at_to_meetings_table` menjaga seluruh modul production lama tetap tersedia. Placeholder baru yang dibuat melalui alur pembuatan kelas dimulai sebagai draft `Coming Soon` sampai pengumpulan dibuka oleh Aslab atau Laboran.

Jika menggunakan queue asynchronous, restart worker setelah rilis:

```bash
php artisan queue:restart
```

## Perintah operasional

```bash
# Melihat status migrasi
php artisan migrate:status

# Membersihkan seluruh cache aplikasi
php artisan optimize:clear

# Membuat cache production
php artisan optimize

# Menjalankan scheduler secara manual
php artisan schedule:run
```

## Catatan keamanan

- Seluruh route akademik dilindungi autentikasi, persetujuan akun, perubahan password pertama, role, policy, dan proteksi kelas arsip.
- Validasi dan otorisasi selalu dilakukan kembali di server.
- Link Google Drive divalidasi berdasarkan scheme, host, dan pola file.
- Update modul memakai ID tetap dan transaksi database untuk mencegah perpindahan data antar-modul.
- Aksi penting seperti penghapusan, reset password, perubahan role, dan aktivasi semester memakai konfirmasi SweetAlert.
