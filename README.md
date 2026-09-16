# SI Praktikum

Sistem informasi pengelolaan praktikum berbasis Laravel 13, MySQL, Blade, Tailwind CSS, Alpine.js, dan Vite. Aplikasi menangani kelas, semester, presensi, pengumpulan laporan mingguan/final, approval berjenjang, import pengguna, tutorial, arsip, serta ekspor PDF/Excel.

## Persyaratan

- PHP 8.3 atau lebih baru dengan ekstensi PDO MySQL, mbstring, intl, GD, ZIP, dan XML
- MySQL 8+ atau MariaDB yang kompatibel
- Composer 2
- Node.js yang kompatibel dengan Vite 8 dan npm

## Instalasi lokal

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
```

Buat database MySQL, lalu sesuaikan blok `DB_*` pada `.env`. Jika memerlukan akun laboran awal, isi `SEED_LABORAN_ID`, `SEED_LABORAN_EMAIL`, dan `SEED_LABORAN_PASSWORD`.

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
composer run dev
```

Aplikasi lokal tersedia di `http://localhost:8000`.

## Alur akses

- `Mahasiswa`: bergabung ke kelas, mengisi presensi melalui petugas, mengirim/revisi laporan, dan mencetak kartu praktikum.
- `Aslab`: mengelola kelas yang ditugaskan dan melakukan review tahap pertama. Aslab dapat berpindah ke mode Mahasiswa.
- `Laboran`: administrasi pengguna/semester/kelas, tutorial, serta review tahap kedua.
- `Dosen`: mengakses kelas yang diampu dan melakukan review akhir laporan final.

Pengguna hasil import wajib mengganti password awal. Seluruh halaman aplikasi juga mewajibkan autentikasi dan verifikasi email.

## Pemeriksaan kualitas

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

Test menggunakan SQLite in-memory sehingga tidak mengubah database development.

## Deployment produksi

- Gunakan `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL` domain HTTPS yang sebenarnya.
- Gunakan user database khusus dengan password kuat; jangan gunakan akun `root`.
- Konfigurasikan mailer nyata agar verifikasi email dan reset password dapat diterima pengguna.
- Jangan menyalin `APP_KEY` antar aplikasi dan jangan commit `.env`.
- Jalankan `php artisan migrate --force`, `php artisan storage:link`, `npm ci && npm run build`, lalu `php artisan optimize` pada proses rilis.
- Jalankan queue worker di bawah process manager jika queue asynchronous diaktifkan.
- Arahkan document root web server ke direktori `public`, bukan root repository.
