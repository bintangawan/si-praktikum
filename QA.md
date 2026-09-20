# QA Alur SI-Praktikum dan Rekomendasi Penyesuaian

## Status implementasi — 19 September 2026

**Rekomendasi audit telah ditindaklanjuti melalui perubahan kode.** Bagian audit di bawah dipertahankan sebagai catatan kondisi awal, bukan gambaran kondisi aplikasi setelah implementasi. Instruksi lanjutan pengguna mengganti rekomendasi verifikasi email dengan **verifikasi akun oleh Laboran atau Aslab**.

### CRUD kelas dan audit performa lanjutan

- Daftar kelas Laboran sekarang menyediakan aksi **Edit kelas** dan **Hapus kelas**. Edit mencakup nama, kelompok, semester mahasiswa, dosen, dan Aslab; perubahan petugas tetap masuk riwayat penugasan.
- Penghapusan bersifat aman: kelas yang belum memiliki presensi/laprak dapat dihapus beserta modul dan pivot pesertanya melalui foreign-key cascade. Kelas yang telah memiliki data akademik ditolak dan diarahkan untuk dipertahankan sebagai arsip.
- Daftar kelas aktif dipaginasi 12 kartu, arsip 24 kartu, tutorial 12 kartu, dan antrean review 25 baris agar ukuran response tidak terus membesar mengikuti data.
- Detail kelas petugas tidak lagi mengambil seluruh submission, riwayat, reviewer, dan presensi semua mahasiswa. Kartu modul memakai aggregate count; data submission, feedback, dan presensi hanya diambil untuk mahasiswa yang sedang membuka kelas.
- Query langsung tugas final dari Blade dihapus. Controller melakukan eager loading terarah sehingga tampilan tidak menambah query tersembunyi.
- Halaman arsip memakai `withCount` sehingga jumlah mahasiswa tidak menghasilkan N+1 query per kartu.
- Index ditambahkan untuk approval pengguna, semester aktif, daftar kelas per role, reverse lookup peserta, antrean review submission, dan filter tutorial.
- Iframe tutorial/preview dan avatar detail memakai lazy loading. Sidebar memakai `x-cloak` untuk menghilangkan kilatan posisi sebelum Alpine aktif.
- Konfigurasi lokal memakai session/cache file dan queue sinkron serta menonaktifkan Debugbar, sehingga request lokal tidak menambah query session/cache dan collector debug pada setiap halaman.

### Penyesuaian visual dan penyederhanaan tugas

- Aksi, route, controller, modal, dan error auto-open untuk **Buat Laprak Final** telah dihapus. Penambahan tugas dilakukan melalui modul/pertemuan. Data final lama tetap dapat dibaca dan diperiksa agar kompatibilitas data tidak rusak.
- Seluruh gradient dihapus. Warna antarmuka dibatasi pada Emerald untuk brand/sukses, Slate untuk surface/netral, Amber untuk pending/revisi, dan Red untuk error/ditolak.
- Token Tailwind `gray` dipetakan ke skala Slate sehingga template lama yang masih memakai nama class `gray-*` menghasilkan warna netral yang sama.
- Poppins menjadi font utama pada HTML, form control, landing page, guest layout, dan deklarasi PDF. Override monospace/serif di tampilan web dihapus.
- Tidak ada teks web di bawah 12 px. Tracking label kecil dikurangi agar tetap terbaca pada layar sempit.
- Shell aplikasi memakai `100dvh`, main content memiliki `min-w-0`, form action menumpuk pada mobile, dan tabel lebar mempunyai ukuran minimum di dalam container horizontal-scroll agar kolom tidak gepeng atau menyebabkan overflow halaman.
- Ditambahkan `UiConsistencyTest` untuk mencegah gradient, warna di luar palette, font mono/serif, teks kurang dari 12 px, tabel tanpa container scroll, dan regresi shell responsif.

### Penyesuaian lanjutan: pembuatan kelas dan status modul

- Halaman Buat Kelas sekarang sekaligus memuat builder modul dinamis. Laboran dapat menambah atau menghapus baris, dengan minimal satu dan batas operasional 50 modul per kelas.
- Setiap modul berisi judul, link file materi Google Drive, instruksi laprak, dan deadline. Nomor modul disusun ulang otomatis ketika baris dihapus.
- Kelas dan seluruh modul disimpan dalam satu transaksi. Jika salah satu modul tidak valid, kelas maupun modul lain tidak tersimpan sebagian.
- Setelah berhasil, laboran diarahkan ke detail kelas agar dapat langsung melihat kartu modul dan kode enrollment.
- Setiap record modul tetap menjadi satu slot upload laprak terpisah bagi setiap mahasiswa.
- Status kartu mahasiswa dinormalisasi menjadi: **Belum mengumpulkan**, **Menunggu pemeriksaan**, **Diterima**, **Revisi**, dan **Ditolak**.
- Pemeriksa kini dapat memilih Terima/ACC, Minta revisi, atau Tolak laporan. Revisi dan penolakan wajib disertai feedback. Keduanya membuka kesempatan mahasiswa mengirim versi perbaikan, termasuk setelah deadline, lalu status pemeriksaan kembali ke Pending pada versi baru.
- Status Ditolak dicatat sebagai event `Rejected` pada riwayat dan tidak diteruskan ke pemeriksa berikutnya.

### Alur akun yang berlaku sekarang

1. Mahasiswa daftar menggunakan NIM, nama, email, dan password. Role selalu Mahasiswa; field persetujuan dari request tidak dipercaya.
2. Tidak ada email verifikasi yang dikirim saat daftar. Pengguna langsung melihat halaman menunggu verifikasi akun.
3. Laboran dan Aslab membuka menu **Verifikasi mahasiswa**. Keduanya dapat memverifikasi **satu akun** atau **semua akun pending**, termasuk akun di halaman pagination lainnya.
4. Persetujuan menyimpan waktu dan ID petugas. Klik ulang tidak menimpa petugas/waktu persetujuan pertama. Bulk approval hanya berlaku untuk akun Mahasiswa, bukan akun petugas.
5. Akun pending belum boleh membuka fitur akademik, enroll, atau memakai endpoint verifikasi. Setelah disetujui, mahasiswa dapat langsung masuk fitur akademik tanpa verifikasi email.
6. Akun lama dipertahankan aksesnya oleh migration. Mahasiswa yang diimpor setelah perubahan masuk antrean verifikasi; akun petugas yang dibuat lewat impor laboran disetujui sebagai bagian provisioning. Kewajiban mengganti password pertama untuk akun impor/reset tetap berlaku.

`email_verified_at` dan endpoint autentikasi email lama dipertahankan untuk kompatibilitas, tetapi **tidak menjadi syarat akses aplikasi**. Persetujuan akun memakai kolom terpisah `approved_at`/`approved_by`.

### Pemetaan tindak lanjut

| Temuan | Implementasi |
|---|---|
| QA-01 | Action tambah pertemuan/final dan URL rekap/ekspor memakai model kelas sehingga route menghasilkan slug. Test mengikuti action hasil render. |
| QA-02 | Pengumpulan modul/final memakai link Drive wajib; request file ditolak server, termasuk file yang dikirim bersama link valid. Pembacaan PDF/history lama tetap tersedia dan diuji otorisasinya. |
| QA-03 | Parser Drive membatasi HTTPS dan host tepat, menolak folder/host asing, mempertahankan resource key. Preview menggunakan binding `src`, tanpa interpolasi URL ke HTML. Petunjuk izin Drive dan tombol buka PDF tersedia. |
| QA-04 | Form Buat Kelas mewajibkan minimal satu modul dan dapat menambah modul secara dinamis hingga batas operasional 50. Menu Kelola modul tetap menyiapkan delapan modul awal, mendukung hingga 50, menyimpan paket secara atomik, dan memperbarui modul tanpa menghapus laporan. Mahasiswa melihat kartu tiap modul dengan slot pengumpulan independen. |
| QA-05 | Versi baru mereset ACC/timestamp seluruh pemeriksa. Update dan review memeriksa versi di dalam transaksi dengan row lock. Kirim awal ganda dan request dari versi lama ditolak; dokumen selesai tidak dapat ditimpa. |
| QA-06 | Selection dibersihkan langsung sebelum debounce, hasil lama dibuang, response out-of-order diabaikan, exact NIM diprioritaskan, wildcard di-escape, keyboard didukung. Hanya mahasiswa yang disetujui bisa disarankan/ditambahkan. |
| QA-07 | Label presensi kartu mengenali Tanpa Keterangan/TK; diuji melalui simpan presensi dan render mahasiswa. |
| QA-08 | Diganti sesuai instruksi pengguna: approval oleh Laboran/Aslab, individual dan semua akun. CTA register dan penjelasan onboarding ditambahkan; NIM disimpan sebagai string angka hingga 20 karakter. |
| QA-09 | Editor mencakup judul, link, instruksi, deadline dan error. Form pertemuan tunggal juga dilengkapi instruksi/deadline, old input dan error. Nomor berikutnya memakai max+1. |
| QA-10 | Middleware menolak mutation kelas arsip. Halaman pengumpulan dan form pengelola menjadi baca saja. Tugas Saya memisahkan semester aktif/arsip; antrean pemeriksaan untuk semester aktif. Pembukaan ulang dilakukan melalui aktivasi semester yang sudah ada. |
| QA-11 | Laboran dapat mengganti dosen/Aslab dengan validasi role dan log penugasan. Pencabutan Aslab memeriksa penugasan semester aktif; histori kelas lama tetap tersimpan. Cakupan laboran tetap global dan arsip diselaraskan. Role Aslab tetap tunggal sebagaimana logic sebelumnya. |
| QA-12 | `document_version` terpisah dari nomor event riwayat, ditambahkan juga pada history lama melalui migration. Preview versi dan pembandingan dua dokumen tetap tersedia. |
| QA-13 | Kartu membedakan belum kirim, menunggu pemeriksaan, revisi, ditolak, dan selesai. Tugas Saya langsung membuka tugas terkait. Informasi deadline tidak lagi menyatakan mahasiswa terlambat hanya karena ACC belum lengkap. |
| Visual | Warna utama emerald/teal mengikuti login; tipografi kecil dinaikkan, bobot/capitalization dirapikan, shared controls diperbarui. Sidebar utama 224 px, ikon/label flex, default mobile tertutup; informasi kelas dilipat agar kartu mendapat ruang utama. |

### Hasil validasi implementasi

- `php vendor/bin/phpunit --no-progress`: **PASS — 70 tests, 508 assertions**. Menggunakan SQLite in-memory, bukan reset database aplikasi.
- `node --test tests/js/workflow.test.mjs`: **PASS — 4 tests** untuk parsing link, selection autocomplete, response pencarian terbalik, keyboard selection dan error pencarian.
- `php vendor/bin/pint --dirty`: **PASS**.
- `git diff --check`: **PASS**.
- `npm run build`: **PASS**. Ada pemberitahuan non-blocking mengenai usia data Browserslist; tidak menghalangi build.
- Empat migration tanggal `2026_09_19` telah diterapkan pada database aplikasi lokal, termasuk perluasan status `Ditolak` dan index performa, tanpa `migrate:fresh`, reset, atau seeding ulang.
- Browser tidak dijalankan, sesuai instruksi pengguna. Pengujian tampilan berupa render Blade/HTTP assertions dan build aset; tidak diklaim sebagai QA visual perangkat.

### Batas layanan eksternal dan kompatibilitas

- Preview bergantung akses file Google Drive. Validator membuktikan bentuk link file yang didukung, bukan MIME file atau permission aktual; tidak ada integrasi API Drive/OAuth atau download isi PDF ke server.
- Mahasiswa diarahkan memakai file Drive baru untuk tiap revisi. URL tidak menjamin snapshot immutable jika isi file diubah langsung di Drive; aplikasi menyimpan riwayat link/versi, bukan salinan PDF.
- PDF lokal yang sudah ada tidak dihapus atau dipindahkan otomatis. Jalur baca lama dipertahankan; seluruh pengumpulan baru memakai link saja.
- Editor menyimpan sekaligus dan langsung mempublikasikan. Tidak ditambahkan workflow draft baru agar konsep meeting/pertemuan yang sudah ada tetap dipertahankan.
- Semua perubahan di atas berada pada working tree; tidak dibuat commit atau deployment eksternal.

---

## Catatan audit awal (sebelum implementasi)

Tanggal audit: 19 September 2026. Metode: audit statis terhadap kode working tree saat ini, termasuk perubahan lokal yang sudah ada sebelum audit.

**Kesimpulan: belum sepenuhnya sesuai kebutuhan.** Fondasi registrasi mahasiswa, enrollment token, pencarian peserta, pengumpulan per pertemuan, dan persetujuan bertahap sudah ada. Namun, terdapat penghambat URL pada aksi laboran, pengumpulan saat ini mengutamakan PDF lokal, belum ada pengelolaan delapan modul sekaligus, dan tampilan internal belum konsisten dengan login.

## 1. Ruang lingkup dan batas hasil

- Dibaca: routes, controller autentikasi/kelas/pertemuan/submission/final/semester/peserta/presensi, policy, model, migration terkait, template mahasiswa/laboran/login/layout, konfigurasi tampilan dan storage, serta kode test yang relevan.
- Audit ini tidak menjalankan browser, mengirim email, mencoba akses Google Drive, menjalankan migrasi, menjalankan test suite, atau memodifikasi database. Status di bawah adalah penilaian berdasarkan kode, bukan sertifikasi bahwa aplikasi berjalan di deployment.
- Test yang disebut adalah **test yang tersedia**, bukan test yang dinyatakan lulus pada audit ini. Test runtime dapat menghasilkan log, cache, dan file sementara; tidak dijalankan agar pekerjaan tetap sesuai permintaan hanya membuat dokumen.
- Ketersediaan layanan email, migration yang sudah diterapkan, permission file Drive, serta tampilan aktual di perangkat masih perlu diverifikasi pada tahap implementasi.
- Tidak ada kode aplikasi yang diubah. File keluaran audit hanya `QA.md`. Perubahan lokal aplikasi yang sudah ada sebelumnya dipertahankan.

## 2. Ringkasan kesesuaian

| Kebutuhan | Hasil dari kode | Status |
|---|---|---|
| Daftar sebagai mahasiswa | `/register` tersedia, role default database Mahasiswa, password di-hash, langsung login; dashboard mewajibkan verifikasi email | Ada, perlu merapikan onboarding |
| Masuk menggunakan NIM/email | `LoginRequest` memilih kolom `id` atau `email`, ada pembatasan percobaan login | Ada |
| Join kelas dengan token | Token dinormalisasi, kelas harus semester aktif, enrollment berulang tidak menggandakan peserta | Sesuai pada jalur dasar |
| Laboran cari nama/NIM dan tambah peserta | API dan autocomplete tersedia, hanya mahasiswa yang belum masuk kelas, maksimal 10 hasil | Ada, beberapa kasus tepi belum benar |
| Laboran membuat modul/pertemuan | Endpoint ada, tetapi form memakai ID sementara binding kelas memakai slug | Terhambat dari UI |
| Delapan modul menjadi delapan tempat pengumpulan | Delapan record meeting akan menghasilkan delapan entri terurut dan endpoint pengumpulan terpisah | Sebagian; belum ada attach/buat massal dan grid kartu modul |
| Laprak hanya link Drive | Backend masih menerima link, tetapi UI mewajibkan file PDF dan service menyimpan ke disk lokal | Tidak sesuai |
| Preview dokumen | Ada iframe dan konversi sebagian bentuk URL Drive; belum ketat memvalidasi sumber atau menangani akses gagal | Sebagian |
| Aslab kemudian laboran menyetujui laprak | Urutan dasar dijaga server; perubahan dokumen masih bisa membawa ACC lama | Sebagian |
| Final: Aslab → laboran → dosen | Urutan dasar tersedia, masalah versi persetujuan serupa | Sebagian |
| Rekap dan ekspor presensi | Backend ada, beberapa link UI masih ID sehingga salah binding | Terhambat dari UI |
| Warna dan kerapian seperti login | Login emerald/teal/slate; interior dominan indigo dan tipografi sangat kecil/tebal | Belum sesuai |
| Sidebar ringkas | Sidebar utama 256 px; detail kelas juga memakai sepertiga grid untuk panel informasi | Perlu penyesuaian |

## 3. Alur yang sudah ada

### 3.1 Mahasiswa

1. Buka halaman depan → daftar. Link register tersedia di `resources/views/welcome.blade.php`; halaman login belum menyediakan tautan langsung ke daftar.
2. Isi ID, nama, email, password, konfirmasi. `RegisteredUserController::store()` tidak mengambil role dari input; migration users memberi default `Mahasiswa`. `is_first_login` diset false untuk pendaftar mandiri.
3. Event `Registered` dikirim, pengguna login, lalu diarahkan ke dashboard. Karena `User` mengimplementasikan `MustVerifyEmail` dan route dashboard memakai `verified`, pengguna baru harus memverifikasi email sebelum masuk fitur akademik.
4. Buka kelas dan masukkan token. `CourseController::enroll()` mengubah token ke huruf besar, trim, mencari kelas semester aktif, lalu `syncWithoutDetaching()`.
5. Buka kelas yang diikuti. `CoursePolicy::view()`/`participate()` membatasi akses mahasiswa berdasarkan keanggotaan.
6. Pilih pertemuan. `SubmissionController::manage()`/`store()` mengikat pengumpulan pada mahasiswa dan meeting tersebut. Deadline berlaku; revisi diizinkan setelah deadline bila ada status REVISI; pengumpulan lengkap terkunci untuk mahasiswa.
7. Saat ini mahasiswa memilih PDF lokal, melihat preview, mengirim, dan menunggu Aslab → laboran. Final menambahkan tahap dosen.

Poin yang sudah baik: role pendaftar tidak bebas dipilih, token semester lama ditolak, duplikasi enrollment ditangani, kepemilikan submission diperiksa, dan constraint unik melindungi peserta/pertemuan/submission bila migration terkait sudah diterapkan.

### 3.2 Laboran

1. Login; akun hasil impor/reset diarahkan mengganti password. Akun belum terverifikasi juga harus menyelesaikan verifikasi email.
2. Buat semester dan aktifkan. Pembuatan kelas ditolak bila tidak ada semester aktif.
3. Buat kelas: nama, grup, target semester, dosen dan Aslab. Role dosen/Aslab divalidasi, laboran pembuat dicatat, token unik delapan karakter dibuat.
4. Bagikan token atau buka daftar peserta dan tambah mahasiswa dari suggestion.
5. Buat pertemuan/modul beserta link materi; atur deadline; kelola presensi.
6. Periksa antrean yang sudah ACC Aslab; ACC atau minta revisi dengan feedback wajib.
7. Kelola final, pantau progres, lihat rekap dan arsip.

**Hambatan saat ini:** langkah membuat pertemuan/final dan beberapa navigasi rekap menggunakan URL yang tidak sesuai route binding. Selain itu, UI tidak menyediakan pengubahan penugasan kelas, padahal pencabutan Aslab meminta penugasan diganti dahulu.

## 4. Temuan dan tindakan yang disarankan

Prioritas: **P1** = penghambat alur inti/integritas penting; **P2** = ketepatan alur/UX; **P3** = penyempurnaan. Tidak semua keputusan produk di bagian ini adalah bug; yang membutuhkan penetapan aturan disebut eksplisit.

### QA-01 — P1 — Form modul/final dan link rekap masih memakai ID kelas

**Bukti:** `app/Models/Course.php::getRouteKeyName()` mengembalikan `slug`. Namun:

- `resources/views/courses/partials/modals.blade.php:5`: `route('meetings.store', $course->id)`.
- File yang sama, baris 35: `route('final-tasks.store', $course->id)`.
- `resources/views/courses/partials/sidebar.blade.php:89`: rekap presensi memakai `$course->id`.
- `resources/views/attendance/report.blade.php:13` dan `:18`: ekspor PDF/Excel memakai `$course->id`.

**Dampak/reproduksi yang diturunkan dari kode:** untuk kelas ID 1 dengan slug `pemrograman-web-a`, form membuat `/courses/1/meetings`, sedangkan binding mencari `slug=1`; kelas tidak ditemukan dan respons 404 sebelum controller berjalan. Berlaku serupa pada final dan rekap. `CourseManagementTest` sendiri mengharapkan URL peserta dengan ID menghasilkan 404.

**Rekomendasi:** kirim model `$course` pada seluruh route dengan parameter `{course}`; audit semua link/form sejenis, termasuk ekspor. Pertahankan ID numerik untuk route `{meeting}`/`{submission}` yang memang belum memakai slug.

**Kriteria selesai:** submit form yang benar-benar dirender berhasil membuat pertemuan/final; klik rekap dan kedua ekspor berhasil. Test perlu mengambil `action`/`href` hasil render; test yang hanya memanggil `route(..., $course)` tidak menangkap bug template ini.

### QA-02 — P1 — Pengumpulan PDF lokal bertentangan dengan kebutuhan link saja

**Bukti:** kedua controller pengumpulan mempunyai `validateSubmission()` yang menerima `submission_file` PDF sampai 10 MB atau `submission_link`. Kedua handler mahasiswa menggunakan input file wajib tanpa kolom link. `app/Services/SubmissionFileStorage.php::store()` memakai disk `local`; `config/filesystems.php` mengarahkannya ke `storage/app/private`.

**Dampak:** laporan disimpan di server aplikasi pada `submissions/{course-id}/{student-id}/{uuid}.pdf`. Ini bukan penyimpanan isi PDF di database atau otomatis masuk Git, tetapi tetap menambah disk dan backup server. Revisi mempertahankan file lama untuk riwayat, sehingga pemakaian tumbuh. Sebagai ilustrasi kapasitas, 40 mahasiswa × 8 modul × 10 MB = sekitar 3,2 GB sebelum revisi/final; ini estimasi, bukan pengukuran data aktual.

**Rekomendasi:** ubah laprak mingguan dan final menjadi input link Drive wajib; tolak upload file pada endpoint baru, jangan hanya menyembunyikan input. Simpan link/file ID dan metadata ringan, tanpa download, proxy PDF, salinan lokal, atau base64. Preview dimuat langsung oleh browser dari Drive.

**Penanganan data lama:** inventaris submission/history dengan `file_path` terlebih dahulu. Jangan menghapus migration, kolom, file, atau route pembaca lama secara langsung. Jika sudah ada data, sediakan pemindahan terencana ke Drive dan pemetaan tiap versi, verifikasi link, lalu hentikan jalur lama setelah semua referensi aman. Jika belum ada data, penghapusan jalur lokal dapat lebih sederhana setelah dipastikan.

**Kriteria selesai:** mahasiswa bisa mengirim dan merevisi dengan link saja; kedua endpoint menolak file; submission baru tidak membuat PDF pada storage aplikasi; riwayat lama tetap dapat dibaca selama transisi.

### QA-03 — P1 — Parser Drive dan preview belum membatasi sumber dokumen

**Bukti:** validator `submission_link` dan `module_drive_link` hanya memeriksa URL umum. `Submission::previewUrl()` dan `SubmissionHistory::previewUrl()` mencari pola `/d/` atau `id=` tanpa memeriksa host. Jika pola tidak ditemukan, URL dikembalikan mentah. Handler mahasiswa dan pemeriksa membentuk iframe menggunakan interpolasi URL ke `innerHTML`.

**Dampak:** link di luar Drive dapat diterima, URL non-Drive dengan pola serupa bisa salah dikonversi, folder/link tidak sesuai dapat berakhir preview kosong. Penyusunan HTML dari URL mentah juga merupakan permukaan risiko injeksi yang perlu ditutup; audit ini tidak mengklaim eksploit telah diuji. Belum ada bukti pemeriksaan PDF atau izin akses Drive.

**Rekomendasi:** satu parser/validator bersama untuk materi, laprak, final, dan riwayat. Tetapkan HTTPS dan allowlist host tepat, ekstrak file ID dari format yang didukung, tolak folder/URL tidak sesuai, bentuk URL preview dari ID yang tervalidasi. Buat iframe via DOM dan set properti `src`, bukan interpolasi HTML. Sediakan judul iframe, loading, pesan bantuan akses, serta tombol buka dokumen.

**Catatan implementasi:** format link saja tidak membuktikan dokumen benar-benar PDF, masih tersedia, atau dapat dibaca pemeriksa. Jika perlu jaminan MIME/permission, verifikasi metadata melalui integrasi Drive yang sesuai tanpa mengunduh isi file. Jangan menyatakan iframe `load` sebagai bukti berhasil membaca PDF; halaman penolakan akses juga bisa selesai dimuat. Dukungan parameter akses seperti resource key perlu diuji sebelum normalisasi membuang query string.

**Kriteria selesai:** link Drive file valid mendapat preview, URL asing dan folder ditolak secara jelas, link privat/dihapus mempunyai jalur bantuan dan buka tab, semua lokasi preview memakai aturan yang sama.

### QA-04 — P1 — Belum ada alur pengelolaan delapan modul sebagai satu paket

**Bukti:** `MeetingController::store()` hanya menerima satu `meeting_number` (1–16), judul, deskripsi, link opsional, dan deadline opsional. Modal menambah satu pertemuan. `courses/partials/meeting-list.blade.php` merender daftar vertikal dengan badge P1, P2, dan seterusnya; bukan grid kartu Modul 1–8.

**Yang sudah didukung:** jika delapan record meeting berhasil dibuat satu per satu, loop menampilkan delapan entri terurut; masing-masing punya pengumpulan tersendiri. Link satu file/folder berisi delapan modul tidak otomatis dipisah menjadi delapan record.

**Rekomendasi:** pertahankan meeting sebagai modul bila aturan praktikum satu modul = satu pertemuan. Tambahkan editor beberapa modul: jumlah awal 8, baris Modul 1–8, judul, link materi, instruksi dan deadline masing-masing, kemudian simpan secara atomik. Jumlah tetap dapat disesuaikan, jangan hardcode delapan untuk semua kelas. Jika modul dan pertemuan ternyata berbeda konsep, tetapkan relasinya sebelum membuat model baru.

Tampilkan grid kartu mahasiswa dengan nomor/judul, deadline, materi, status miliknya, feedback terbaru, serta tombol kirim/revisi/lihat. Modul harus tetap muncul walau mahasiswa belum mengumpulkan. Materi Drive dan link laprak mahasiswa adalah dua field berbeda.

**Kriteria selesai:** delapan modul dipublikasikan menghasilkan tepat delapan kartu urut dan delapan slot pengumpulan independen untuk setiap peserta; data kelas atau mahasiswa lain tidak tercampur. Gagal validasi tidak meninggalkan paket setengah tersimpan.

### QA-05 — P1 — Dokumen baru dapat membawa ACC dokumen lama

**Bukti:** `SubmissionController::update()` mempertahankan `aslab_status=ACC`; `FinalTaskController::update()` mempertahankan ACC Aslab dan laboran. Policy membolehkan perubahan selama belum `is_completed`, bukan hanya saat diminta revisi.

**Skenario:** dokumen A sudah ACC Aslab tetapi belum laboran. Sebelum deadline mahasiswa mengganti ke B. Status Aslab tetap ACC; laboran dapat menyetujui B tanpa Aslab pernah memeriksa B. Pada final, penggantian setelah dua tahap ACC dapat langsung menuju dosen.

**Rekomendasi:** persetujuan harus terikat versi dokumen. Default yang disarankan: dokumen/link baru membuat versi baru dan mereset ACC beserta timestamp semua pemeriksa untuk versi baru. Jika kampus menginginkan revisi hanya kembali ke pemeriksa tertentu, simpan aturan eksplisit dan tetap tunjukkan bahwa ACC sebelumnya untuk versi sebelumnya.

Ada risiko request bersamaan: pengecekan policy/deadline terjadi sebelum `lockForUpdate`; status lengkap dapat berubah sebelum update terkunci. Periksa ulang kelayakan update di dalam transaksi setelah lock. Pada aksi review, kirim identitas versi yang dibaca agar pemeriksa tidak tanpa sadar menyetujui versi yang baru diganti.

**Kriteria selesai:** persetujuan tidak berpindah diam-diam ke dokumen baru; request edit yang berlomba dengan ACC terakhir ditolak atau diselesaikan konsisten; review versi kedaluwarsa meminta pemeriksa membuka versi terbaru.

### QA-06 — P2 — Autocomplete sudah ada, tetapi ada celah pilihan lama dan relevansi

**Bukti:** `CourseController::searchStudents()` mencari NIM/nama dengan `LIKE`, hanya role Mahasiswa, mengecualikan peserta kelas, minimum tiga karakter, urut nama, limit 10. View `attendance/students.blade.php` mempunyai debounce 300 ms, loading/error/empty state dan nomor request untuk respons lama. ID terpilih dikirim melalui hidden input.

**Kasus yang perlu diperbaiki:**

1. Setelah memilih A, pengguna mengubah teks ke B dan langsung submit sebelum debounce 300 ms berjalan. `selectedId` baru dikosongkan di `searchStudents()`, sehingga A masih bisa dikirim. Kosongkan selection langsung pada event input, sedangkan request pencarian tetap debounce.
2. Suggestion lama tetap tersedia selama request berikutnya berjalan. Kosongkan/nonaktifkan hasil saat query berubah dan pastikan pilihan berasal dari query terbaru.
3. `%` dan `_` menjadi wildcard SQL, bukan pencarian literal. Gunakan escaping yang sesuai database dan validasi setelah trim; jangan menganggap parameter binding otomatis meng-escape wildcard.
4. Urutan alfabet + limit 10 tidak menjamin exact NIM berada teratas. Urutkan exact NIM, prefix NIM/nama, lalu substring; tetap tampilkan NIM untuk nama kembar.
5. Combobox belum memiliki navigasi ArrowUp/ArrowDown, Enter memilih item aktif, dan `aria-activedescendant`/selection yang lengkap.

**Yang dipertahankan:** batas role, pengecualian peserta existing, validasi ID server, anti-duplikasi, debounce, dan perlindungan respons out-of-order. Endpoint tersedia untuk laboran serta Aslab yang ditugaskan; dosen tidak mempunyai route tambah peserta.

**Kriteria selesai:** ketik cepat/ganti pilihan tidak pernah menambahkan orang lama; exact NIM diprioritaskan, nol awal NIM tetap utuh, nama kembar tidak ambigu, keyboard bisa digunakan, nonpeserta yang valid saja dapat ditambahkan.

### QA-07 — P2 — Status Tanpa Keterangan salah ditampilkan pada kartu pertemuan

**Bukti:** `AttendanceController::normalizeStatus()` menyimpan tidak hadir sebagai `Tanpa Keterangan`. `courses/partials/meeting-list.blade.php` hanya mengenali `ALPA`/`ALPHA`, sehingga `TANPA KETERANGAN` jatuh ke default `Belum Presensi` walau record sudah ada.

**Rekomendasi:** pusatkan mapping status presensi; gunakan label seragam Tanpa Keterangan/TK pada daftar modul, rekap, dan kartu cetak.

**Kriteria selesai:** simpan TK melalui laboran → mahasiswa melihat TK, bukan Belum Presensi; H/S/I juga konsisten dengan rekap.

### QA-08 — P2 — Onboarding registrasi dan email belum jelas

**Bukti:** form register memakai label NIM/NIP dan beberapa teks Inggris, walau pendaftar mandiri selalu mahasiswa. ID hanya divalidasi string unik maksimal 20, belum format NIM kampus. Login tidak memiliki tautan daftar langsung. `.env.example` memakai `MAIL_MAILER=log`, yang bukan bukti email deployment dikirim ke inbox.

**Rekomendasi:** tambahkan CTA Daftar Mahasiswa pada login, ubah label menjadi NIM, beri pesan verifikasi setelah daftar, dan validasi format NIM berdasarkan aturan kampus sambil mempertahankan string/nol awal. Tetapkan role Mahasiswa secara eksplisit di controller untuk memperjelas kontrak. Uji pengiriman dan pengiriman ulang email pada environment pengujian; jangan menghilangkan middleware verifikasi sebagai jalan pintas.

Akun impor belum diberi `email_verified_at`, dan import tidak memicu event `Registered`. Setelah mengganti password, pengguna perlu mengakses halaman verifikasi dan meminta email. Sediakan instruksi onboarding/import yang menjelaskan langkah ini.

**Kriteria selesai:** pendaftar baru paham status akun, dapat verifikasi, lalu enroll; percobaan menyisipkan role Laboran di request tidak mengubah role; NIM ganda ditolak; akun impor mempunyai jalur verifikasi yang jelas.

### QA-09 — P2 — Form modul belum mencakup data dan error yang diperlukan

**Bukti:** modal tambah pertemuan hanya mengisi nomor/judul/link; backend menerima description/deadline tetapi field tidak ada di modal. Nomor awal memakai `count()+1`, yang dapat bertabrakan jika urutan berlubang (misalnya record 1 dan 3 → default 3). Tidak ada error field/old input atau pembukaan ulang modal pada kegagalan validasi di partial ini.

**Rekomendasi:** masukkan instruksi dan deadline di editor modul, tentukan link materi wajib sebelum publish, gunakan nomor kosong berikutnya atau max+1 dengan batas jelas, tampilkan error per field dan pertahankan input. Tampilkan aksi edit materi yang mudah ditemukan. Pisahkan draft/publish bila laboran membutuhkan penyiapan sebelum mahasiswa melihat modul.

**Kriteria selesai:** error nomor duplikat/link tidak valid terlihat tanpa kehilangan isian; deadline dan instruksi bisa diisi saat setup; kartu menampilkan instruksi atau menyediakan detailnya.

### QA-10 — P2 — Semester arsip belum mempunyai aturan mutasi konsisten

**Bukti:** enrollment token membatasi semester aktif, tetapi `CoursePolicy::manage()`/`participate()` dan aksi tambah peserta/modul/pengumpulan tidak memeriksa semester aktif. `mySubmissions()` dan antrean pending juga tidak memfilter semester aktif. Arsip bukan otomatis read-only.

**Dampak:** anggota dapat tetap mengirim pada kelas arsip jika deadline/status membolehkan; pengelola dapat mengubah data lama. Ini perlu keputusan produk, bukan otomatis salah jika memang revisi semester lama diizinkan.

**Rekomendasi default:** arsip read-only, dan bila dibutuhkan sediakan pembukaan ulang eksplisit untuk revisi. Terapkan di server dan UI. Tugas Saya default semester aktif dengan filter arsip; antrean pemeriksa menampilkan semester dengan jelas.

**Kriteria selesai:** perpindahan semester tidak mencampur tugas tanpa penjelasan, dan mutasi arsip mengikuti aturan yang ditetapkan.

### QA-11 — P2 — Pengelolaan penugasan dan cakupan laboran perlu dituntaskan

**Bukti:** `UserController::revokeAslab()` menolak pencabutan jika ada kelas yang menunjuk Aslab, termasuk kelas lama, dan meminta penugasan diganti. Namun `routes/web.php`/`CourseController` belum menyediakan update penugasan kelas. Promosi mahasiswa ke Aslab mengganti role global; endpoint mahasiswa kemudian ditolak. Test `test_aslab_role_is_fixed_and_cannot_be_switched_into_student_access` menunjukkan role tunggal memang disengaja saat ini.

`CoursePolicy` membolehkan semua laboran mengelola semua kelas; antrean laboran juga global, sedangkan daftar kelas default dan arsip laboran memfilter kelas miliknya.

**Rekomendasi:** tambahkan penggantian Aslab/dosen melalui pengelolaan kelas dengan validasi role dan riwayat penugasan; tentukan penanganan histori kelas arsip. Jelaskan dampak promosi role sebelum aksi. Pertahankan role tunggal kecuali kampus memerlukan Aslab sekaligus peserta kelas lain. Tetapkan apakah laboran administrator global atau terbatas penugasan, lalu selaraskan label/filter dan policy. Hak global saat ini tidak dinyatakan sebagai kebocoran tanpa keputusan cakupan tersebut.

**Kriteria selesai:** laboran bisa menyelesaikan pergantian petugas tanpa edit database manual; data approval lama tetap memiliki atribusi; cakupan kelas dapat dipahami dari UI.

### QA-12 — P2 — Riwayat tindakan belum sama dengan versi dokumen

**Bukti:** `recordHistory()` menaikkan `iteration` baik saat upload, revisi, maupun ACC. Handler mahasiswa memberi label `Versi #iteration`. Dengan demikian satu dokumen yang mendapat dua ACC dapat tampak memiliki beberapa versi padahal filenya sama.

**Rekomendasi:** pisahkan versi dokumen dan log review. Versi bertambah hanya saat penggantian dokumen; approval/feedback mengacu pada versi itu. Tampilkan siapa, kapan, aksi apa, dan feedback. Link Drive yang sama bisa berubah isinya di luar aplikasi: untuk revisi yang dapat dibandingkan, minta file Drive baru per versi atau gunakan mekanisme versi yang benar-benar dapat dirujuk. Jangan menjanjikan snapshot immutable hanya dari URL.

**Kriteria selesai:** upload v1 → ACC Aslab → revisi laboran tetap dokumen v1 dengan beberapa event; kirim file baru menghasilkan v2; pembanding menunjukkan dua versi dokumen yang berbeda.

### QA-13 — P2 — Bahasa status deadline dan pintasan tugas membingungkan

**Bukti:** kartu pertemuan menampilkan `Lewat Deadline & Belum ACC Sepenuhnya` untuk submission belum lengkap, meskipun mahasiswa sudah mengirim tepat waktu. Halaman Tugas Saya mengarahkan aksi utama ke detail kelas, bukan langsung ke handler tugas tersebut. Revisi laboran belum diperlakukan sama dengan revisi Aslab pada warna tombol kartu.

**Rekomendasi:** pisahkan status pengumpulan (belum kirim/tepat waktu/terlambat), status pemeriksaan, dan kesempatan revisi. Jangan menyiratkan mahasiswa terlambat hanya karena pemeriksa belum ACC. Simpan identitas meeting/final pada view model Tugas Saya agar CTA menuju modul tepat. Samakan penanda revisi dari semua pemeriksa.

**Kriteria selesai:** mahasiswa langsung membuka tugas yang dipilih dan memahami apakah menunggu review, perlu revisi, atau terkunci.

## 5. Spesifikasi alur target untuk implementasi

### 5.1 Setup dan publikasi modul

Laboran aktifkan semester → buat kelas dan petugas → buat paket modul (misalnya 8) → lengkapi tiap judul/link materi/instruksi/deadline → validasi → publikasi → mahasiswa terdaftar melihat kartu Modul 1–8.

Gunakan entitas meeting yang ada sebagai titik awal; jangan membuat tabel baru hanya untuk mengganti nama kartu. Setiap modul punya identitas tetap, dan pengumpulan unik per mahasiswa + modul. Penambahan modul di kemudian hari tidak mereset submission modul lama.

### 5.2 Pengumpulan Drive

Mahasiswa buka kartu → baca materi → unggah PDF ke Drive miliknya → atur akses baca sesuai kebijakan kampus → tempel link file → preview → kirim link → pemeriksa melihat versi terkait → ACC atau feedback revisi → mahasiswa mengirim versi baru bila diminta.

Data aplikasi cukup menyimpan hubungan mahasiswa/modul, link asli yang valid, file ID/metadata seperlunya, versi, catatan, timestamp, dan review. Tidak ada file PDF laprak baru yang disimpan server. Ekspor rekap/kartu praktikum PDF yang dibuat aplikasi merupakan fitur berbeda dan tidak perlu dihapus karena permintaan link-only laprak.

### 5.3 Review

| Jenis laporan | Urutan | Syarat selesai |
|---|---|---|
| Per modul | Aslab → laboran | Keduanya ACC pada versi yang berlaku |
| Final | Aslab → laboran → dosen | Ketiganya ACC pada versi yang berlaku |

Tombol review hanya aktif pada tahap yang sesuai. Backend tetap memvalidasi tahap dan versi. Feedback wajib untuk revisi, ditampilkan pada kartu/detail mahasiswa. Setelah lengkap, mahasiswa tidak dapat mengganti dokumen melalui UI maupun request langsung. Pembukaan ulang review, bila diizinkan, perlu aksi dan log yang jelas.

## 6. Arahan UI: jadikan login referensi visual

Ini rekomendasi desain berdasarkan kelas CSS/template, belum penilaian visual hasil render. Referensi utama: `resources/views/auth/login.blade.php` dan `resources/views/layouts/guest.blade.php`.

### 6.1 Palette yang disarankan

| Peran warna | Token/acuan | Nilai |
|---|---|---|
| Primary | Emerald 700 | `#047857` |
| Primary hover | Emerald 800 | `#065F46` |
| Aksen gradient tombol utama | Teal 600 | `#0D9488` |
| Brand gelap | Emerald 950 / 900 | `#022C22` / `#064E3B` |
| Background aplikasi | Slate 50 | `#F8FAFC` |
| Surface kartu/form | White | `#FFFFFF` |
| Border | Slate 200 | `#E2E8F0` |
| Judul | Slate 900 | `#0F172A` |
| Teks isi | Slate 700 | `#334155` |
| Teks sekunder | Slate 500 | `#64748B` |
| Sukses/ACC | Emerald 700 pada Emerald 50 | `#047857` / `#ECFDF5` |
| Pending/peringatan | Amber 800 pada Amber 50 | `#92400E` / `#FFFBEB` |
| Error/revisi | Red 700 pada Red 50 | `#B91C1C` / `#FEF2F2` |

Gunakan token/komponen bersama, bukan penggantian warna massal tanpa melihat makna status. Indigo/purple tidak perlu menjadi warna utama per halaman. Label status tetap wajib agar informasi tidak hanya bergantung warna.

### 6.2 Tipografi, kartu dan form

- Pertahankan Poppins yang sudah dikonfigurasi di `tailwind.config.js` dan kedua layout.
- Judul halaman 24–30 px, judul kartu 16–18 px, isi/input/tombol 14 px, metadata 12–13 px. Hindari 7–10 px untuk informasi utama dan label form.
- Gunakan sentence case, bobot 500–700 untuk mayoritas isi, judul utama 700–800. Kurangi penggunaan `font-black`, uppercase, dan tracking lebar di seluruh halaman.
- Kartu utama radius 16–24 px dan padding 20–24 px; input radius 12 px dengan tinggi sekitar 44–48 px, border slate dan focus emerald seperti login.
- Bayangan tipis untuk kartu rutin; gradient/bayangan lebih kuat hanya pada aksi utama atau elemen brand. Kurangi panel bertumpuk dengan radius 40 px dan padding 32–40 px yang memakan ruang kerja.
- Buat komponen reusable untuk tombol, field/error, badge status, kartu modul, alert, modal dan preview dokumen. Prioritaskan register, kelas, peserta, modul, submission dan review agar alur utama terasa satu produk.

### 6.3 Sidebar lebih ringkas

**Saat ini:** `layouts/app.blade.php:94–98` memakai `w-64` (256 px) dengan offset tutup `lg:-ml-64`. `courses/show.blade.php` membagi konten tiga kolom, satu untuk informasi kelas dan dua untuk daftar; ini menambah kesan area samping terlalu dominan.

**Target yang disarankan:**

- Desktop sidebar 224 px; collapsed opsional 64–72 px dengan tooltip dan label aksesibel. Jika lebarnya diganti, sinkronkan offset/transform agar tidak menyisakan celah.
- Gunakan putih dengan border slate dan item aktif emerald lembut, atau brand emerald gelap yang konsisten dengan login; rekomendasi utama putih agar ruang kerja terasa ringan.
- Item menu tinggi 40–44 px, ikon 18–20 px, label 13–14 px, gap 10–12 px. Hindari padding vertikal besar dan grup kosong.
- Perbaiki struktur `components/nav-link-sidebar.blade.php`: ikon dan label saat ini berada dalam satu span slot, sehingga parent flex tidak langsung mengatur keduanya sebagai dua item. Gunakan wrapper flex yang tepat.
- Pindahkan ringkasan kelas ke header ringkas atau panel yang bisa dilipat; beri kartu modul lebar konten utama.
- Pada mobile, gunakan drawer overlay maksimum sekitar 280 px, tertutup saat navigasi; kelola Escape, focus, scroll lock dan atribut tombol. Pisahkan preferensi collapse desktop dari status drawer mobile agar nilai localStorage desktop tidak membuka drawer mobile tanpa sengaja.

**Kriteria visual:** evaluasi 360/390, 768, 1024, dan 1440 px; tidak ada overflow horizontal halaman, nama panjang tidak merusak layout, kartu tetap terbaca, tabel boleh scroll di wadahnya, preview mobile mempunyai tinggi yang berguna. Kesamaan palette saja tidak cukup: spacing, hierarki dan kerapatan harus ikut konsisten.

## 7. Urutan pengerjaan yang direkomendasikan

1. **Pulihkan aksi laboran:** QA-01; uji action/href template sebenarnya.
2. **Sesuaikan arsitektur dokumen:** QA-02 dan QA-03; link-only untuk modul/final, parser bersama, jalur data lama aman.
3. **Pastikan integritas review:** QA-05 dan QA-12; identitas versi, reset/aturan ACC, concurrency, riwayat.
4. **Lengkapi modul:** QA-04 dan QA-09; editor paket, publikasi, delapan kartu, deadline/instruksi, error yang jelas.
5. **Rapikan flow peserta dan operasional:** QA-06–QA-08, QA-10–QA-11 dan QA-13.
6. **Terapkan tampilan login ke area aplikasi:** token dan komponen, sidebar, lalu halaman alur utama; lanjutkan QA visual responsif.

Pekerjaan ini adalah usulan tindak lanjut, belum diterapkan dalam audit.

## 8. Rencana pengujian setelah penyesuaian

| Skenario | Hasil yang harus dibuktikan |
|---|---|
| Register NIM valid, nol awal, NIM/email duplikat, role buatan | Mahasiswa terbentuk benar, nol awal utuh, duplikat ditolak, role tidak bisa dinaikkan |
| Verifikasi email dan akun impor | Fitur akademik menunggu verifikasi; ganti password dan kirim ulang email dapat diselesaikan |
| Enroll token valid/lowercase/spasi/invalid/semester lama/duplikat | Hanya kelas aktif yang tepat ditambahkan, tidak ada duplikasi |
| Akses kelas/submission milik orang lain | Ditolak pada GET dan mutation sesuai policy |
| Suggestion nama/NIM/kembar/tidak ditemukan/anggota existing | Hasil tepat, NIM terlihat, anggota existing tidak disarankan |
| Pilih A lalu ketik B dan submit cepat; respons jaringan terbalik | A tidak ditambahkan setelah pilihan tidak lagi sesuai, hasil lama tidak menimpa baru |
| Suggestion keyboard dan wildcard | Navigasi aksesibel, pencarian literal benar, exact NIM diprioritaskan |
| Tambah delapan modul melalui form hasil render | Tidak 404, tepat delapan modul terurut, tidak ada simpan parsial |
| Kirim laprak pada Modul 3 | Hanya submission Modul 3 mahasiswa itu yang berubah; modul lain tetap kosong |
| Link Drive normal/privat/dihapus/folder/non-Drive | Validasi dan fallback benar; preview tidak diklaim berhasil hanya karena iframe load |
| Upload PDF langsung ke endpoint link-only | Ditolak; tidak ada PDF baru tersimpan pada disk aplikasi |
| Aslab/laboran/dosen di luar tahap atau luar penugasan | Ditolak server; tombol sesuai hak dan tahap |
| Ganti dokumen setelah ACC sebagian | Versi baru tidak mewarisi ACC tanpa aturan eksplisit |
| Dua tab mahasiswa/pemeriksa mengirim bersamaan | Tidak ada overwrite status lengkap atau ACC terhadap versi yang tidak dibaca |
| Deadline sebelum/sesudah batas, revisi setelah deadline, selesai | Aturan server dan tampilan konsisten; waktu Asia/Jakarta (`config/app.php`) |
| H/S/I/TK pada presensi | Label mahasiswa, rekap, dan cetak konsisten |
| Arsip dan pergantian Aslab | Mutasi mengikuti kebijakan; penggantian penugasan dapat selesai dari UI |
| Link rekap, PDF, Excel dari halaman sebenarnya | Semua menggunakan slug kelas dan dapat diakses pengguna berhak |
| Riwayat dan data PDF lama | Versi/link lama tetap terbaca sesuai rencana transisi, tidak hilang saat perubahan skema |
| Visual desktop/mobile dan keyboard | Palette konsisten, sidebar ringkas, error terbaca, focus terlihat, modal dan preview dapat digunakan |

### Test yang tersedia dan gap

- `tests/Feature/CourseManagementTest.php`: pembuatan kelas, enrollment, search/add/remove peserta, serta batas role Aslab. Belum membuktikan perilaku debounce/keyboard browser.
- `tests/Feature/AdministrativeWorkflowTest.php`: semester, konten kelas, final dan role; request dibuat langsung dengan model kelas sehingga salah URL di form masih bisa luput.
- `tests/Feature/WorkflowAuthorizationTest.php`: otorisasi, waterfall approval, presensi dan sebagian tampilan. Tambahkan skenario versi dokumen, deadline/revisi, dan race yang relevan.
- `tests/Feature/SubmissionPdfUploadTest.php`: saat ini justru menguji penyimpanan PDF lokal dan retensi revisinya. Sesuaikan dengan keputusan link-only; pertahankan pengujian kompatibilitas baca data lama bila diperlukan.
- `tests/Feature/ApplicationPagesSmokeTest.php`: render halaman dan ekspor; halaman berhasil dirender tidak membuktikan semua action/href yang dihasilkan benar.
- `tests/Feature/Auth/RegistrationTest.php`: register dan redirect dasar; lengkapi role default/role injection, NIM dan rangkaian verifikasi → enroll.

**Kondisi layak dinyatakan sesuai kebutuhan:** tidak ada penghambat aksi laboran, delapan modul dapat dikelola dan muncul sebagai delapan kartu mahasiswa, laprak baru hanya menyimpan link Drive, preview/fallback dapat digunakan kedua peran, review mengacu versi benar, dan UI telah diverifikasi mengikuti arah visual login.
