<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KARTU PRAKTIKUM LABORATORIUM KOMPUTER</title>
    <style>
        @page {
            margin: 1cm 1.5cm;
        }
        body {
            font-family: 'Poppins', 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.25;
            color: #000;
        }

        /* KOP SURAT */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .kop-logo {
            width: 85px;
            text-align: center;
            vertical-align: middle;
        }
        .kop-logo img {
            width: 78px;
            height: auto;
        }
        .kop-text {
            text-align: center;
            vertical-align: middle;
        }
        .kop-text .univ {
            font-size: 13pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .kop-text .fakultas {
            font-size: 11.5pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .kop-text .lab {
            font-size: 12pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .kop-text .alamat {
            font-size: 8.5pt;
            font-weight: bold;
            margin-top: 2px;
        }

        /* WATERMARK LOGO DI BELAKANG KONTEN */
        .watermark {
            position: fixed;
            top: 30%;
            left: 20%;
            width: 60%;
            opacity: 0.08;
            z-index: -1;
        }
        .watermark img {
            width: 100%;
            height: auto;
        }

        .line-double {
            border-top: 2.5px solid #000;
            border-bottom: 1px solid #000;
            height: 3px;
            margin-top: 4px;
            margin-bottom: 16px;
        }

        /* JUDUL DOKUMEN */
        .doc-title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 18px;
            text-transform: uppercase;
        }

        /* BIODATA & FOTO 3x4 */
        .bio-container {
            width: 100%;
            margin-bottom: 14px;
        }
        .bio-table {
            width: 73%;
            float: left;
            border-collapse: collapse;
            font-size: 10.5pt;
        }
        .bio-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .bio-table td.label {
            width: 25%;
            font-weight: bold;
        }
        .bio-table td.sep {
            width: 3%;
        }
        .bio-table td.label2 {
            font-weight: bold;
            padding-left: 150px;
            white-space: nowrap;
        }
        .photo-box {
            width: 25%;
            float: right;
            text-align: center;
        }
        .photo-frame {
            width: 3cm;
            height: 4cm;
            border: 1px solid #000;
            margin: 0 auto;
            text-align: center;
            line-height: 4cm;
            font-size: 10pt;
            font-weight: bold;
            overflow: hidden;
        }
        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .clear {
            clear: both;
        }

        /* TABEL MODUL PRAKTIKUM */
        .practicum-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 18px;
            font-size: 9.5pt;
        }
        .practicum-table th, .practicum-table td {
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: center;
        }
        .practicum-table th {
            font-weight: bold;
            background-color: #ffffff;
            text-transform: uppercase;
        }
        .practicum-table td.title-col {
            text-align: left;
            padding-left: 8px;
        }
        .practicum-table td.no-col {
            height: 22px;
        }

        /* TANDA TANGAN */
        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11pt;
        }
        .ttd-table td {
            width: 50%;
            border: 1px solid #000;
            padding: 6px 10px;
            vertical-align: top;
            text-align: left;
        }
        .ttd-table td.ttd-space {
            height: 80px;
        }
        .ttd-table td.ttd-name {
            height: 45px;
        }

        /* FOOTNOTE / CATATAN + QR CODE */
        .footer-container {
            width: 100%;
            margin-top: 22px;
        }
        .qr-box {
            width: 90px;
            float: left;
        }
        .qr-box img {
            width: 80px;
            height: 80px;
        }
        .notes {
            margin-left: 100px;
            font-size: 8pt;
            font-style: italic;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    {{-- WATERMARK LOGO --}}
    <div class="watermark">
        <img src="{{ public_path('images/logo-uinsu.png') }}" alt="Watermark UINSU">
    </div>

    {{-- KOP SURAT --}}
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                <img src="{{ public_path('images/logo-uinsu.png') }}" alt="Logo UINSU" onerror="this.style.display='none'">
            </td>
            <td class="kop-text">
                <div class="univ">UNIVERSITAS ISLAM NEGERI SUMATERA UTARA MEDAN</div>
                <div class="fakultas">FAKULTAS SAINS DAN TEKNOLOGI</div>
                <div class="lab">LABORATORIUM KOMPUTER</div>
                <div class="alamat">
                    Jl. Lap. Golf, Desa Durian Jangak, Kec. Pancur Batu<br>
                    Kabupaten Deli Serdang, Provinsi Sumatera Utara, Kode Pos 20353<br>
                    Website:www.saintek.uinsu.ac.id, E-mail: saintek@uinsu.ac.id
                </div>
            </td>
        </tr>
    </table>

    <div class="line-double"></div>

    {{-- JUDUL KARTU --}}
    <div class="doc-title">KARTU PRAKTIKUM LABORATORIUM KOMPUTER</div>

    {{-- INFORMASI MAHASISWA & FOTO 3x4 --}}
    <div class="bio-container">
        <table class="bio-table">
            <tr>
                <td class="label">Nama</td>
                <td class="sep">:</td>
                <td colspan="3">{{ strtoupper($student->name) }}</td>
            </tr>
            <tr>
                <td class="label">NIM</td>
                <td class="sep">:</td>
                <td colspan="3">{{ $student->id }}</td>
            </tr>
            <tr>
                <td class="label">Semester</td>
                <td class="sep">:</td>
                <td style="white-space: nowrap;">{{ $course->target_semester ?? $course->semester->semester_name ?? '-' }}</td>
                <td class="label2">Tahun Akademik</td>
                <td style="white-space: nowrap;">: {{ $course->semester->academic_year ?? date('Y') . '/' . (date('Y') + 1) }}</td>
            </tr>
            <tr>
                <td class="label">Mata Kuliah</td>
                <td class="sep">:</td>
                <td colspan="3">{{ strtoupper($course->course_name) }}</td>
            </tr>
        </table>

        <div class="photo-box">
            <div class="photo-frame">
                @if($student->avatar && file_exists(public_path('storage/' . $student->avatar)))
                    <img src="{{ public_path('storage/' . $student->avatar) }}" alt="Foto Profile">
                @else
                    3 x 4
                @endif
            </div>
        </div>
        <div class="clear"></div>
    </div>

    {{-- TABEL PRESENSI DAN VERIFIKASI PRAKTIKUM --}}
    <table class="practicum-table">
        <thead>
            <tr>
                <th width="5%" rowspan="2">NO</th>
                <th width="15%" rowspan="2">TANGGAL<br>PRAKTIKUM</th>
                <th width="45%" rowspan="2">JUDUL PRAKTIKUM</th>
                <th width="35%" colspan="2">VERIFIKASI ASISTEN LAB</th>
            </tr>
            <tr>
                <th width="17.5%">ABSENSI<br>PRAKTIKUM</th>
                <th width="17.5%">PENERIMAAN<br>LAPORAN</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= 8; $i++)
                @php
                    $meeting = $meetings->where('meeting_number', $i)->first();
                    
                    // 1. Tanggal Praktikum dari created_at Meeting
                    $meetingDate = $meeting && $meeting->created_at 
                        ? \Carbon\Carbon::parse($meeting->created_at)->format('d/m/Y') 
                        : '';

                    // 2. Absensi
                    $attendance = $meeting ? $meeting->attendances->first() : null;
                    $statusStr = $attendance ? strtoupper((string)$attendance->status) : '';
                    
                    $absensiText = match($statusStr) {
                        'HADIR', 'H' => 'Hadir',
                        'SAKIT', 'S' => 'Sakit',
                        'IZIN', 'I'  => 'Izin',
                        'TANPA KETERANGAN', 'ALPHA', 'TK', 'A' => 'TK',
                        default => ''
                    };

                    // 3. Status Penerimaan Laporan (ACC Aslab)
                    $accDate = '';
                    if ($meeting && $meeting->submissions) {
                        $sub = $meeting->submissions->first();
                        
                        // Periksa apakah status sudah di-ACC (ACC, APPROVED, LULUS, SELESAI, atau 1)
                        if ($sub && in_array(strtoupper((string)$sub->status), ['ACC', 'APPROVED', 'LULUS', 'SELESAI', '1'])) {
                            // Mengambil tanggal ACC dari acc_at / approved_at / updated_at
                            $dateToParse = $sub->acc_at ?? $sub->approved_at ?? $sub->updated_at;
                            $accDate = $dateToParse ? \Carbon\Carbon::parse($dateToParse)->format('d/m/Y') : '';
                        }
                    }
                @endphp
                <tr>
                    <td class="no-col">{{ $i }}</td>
                    <td>{{ $meetingDate }}</td>
                    <td class="title-col">{{ $meeting->title ?? ('Modul ' . $i) }}</td>
                    <td>{{ $absensiText }}</td>
                    <td>{{ $accDate }}</td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- TANDA TANGAN (LABORAN & DOSEN) --}}
    <table class="ttd-table">
        <tr>
            <td>Disetujui,</td>
            <td>Mengetahui</td>
        </tr>
        <tr>
            <td>Laboran</td>
            <td>Dosen Pengampu</td>
        </tr>
        <tr>
            <td class="ttd-space"></td>
            <td class="ttd-space"></td>
        </tr>
        <tr>
            <td class="ttd-name">
                <u><strong>{{ $course->laboran->name ?? '' }}</strong></u><br>
                NIP: {{ $course->laboran->id ?? '' }}
            </td>
            <td class="ttd-name">
                <u><strong>{{ $course->dosen->name ?? '' }}</strong></u><br>
                NIP: {{ $course->dosen->id ?? '' }}
            </td>
        </tr>
    </table>

    {{-- QR CODE + CATATAN KAKI (FOOTNOTE) --}}
    <div class="footer-container">
        @if(isset($qrCodeBase64))
            <div class="qr-box">
                <img src="{{ $qrCodeBase64 }}" alt="QR Code">
            </div>
        @endif
        <div class="notes">
            *) Batas waktu pengumpulan Laporan (per modul) adalah 1 minggu setelah praktikum.<br>
            *) Jika melewati batas waktu pengumpulan, mahasiswa tidak dapat mengikuti praktikum selanjutnya.
        </div>
        <div class="clear"></div>
    </div>

</body>
</html>
