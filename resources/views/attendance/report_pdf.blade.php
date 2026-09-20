<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Presensi - {{ $course->course_name }}</title>
    <style>
        body { font-family: 'Poppins', 'DejaVu Sans', sans-serif; font-size: 10px; color: #334155; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 0; text-transform: uppercase; font-size: 14px; }
        .header p { margin: 3px 0 0 0; color: #666; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 4px 5px; text-align: left; }
        th { background-color: #f3f4f6; text-transform: uppercase; font-size: 8px; font-weight: bold; }
        .text-center { text-align: center; }
        .badge-success { color: #047857; font-weight: bold; }
        .badge-danger { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Rekap Presensi Mahasiswa</h2>
        <p>Mata Kuliah: <strong>{{ $course->course_name }}</strong> (Kelas: {{ $course->class_group }})</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="3%">No</th>
                <th width="10%">NIM</th>
                <th width="20%">Nama Mahasiswa</th>
                
                {{-- Dynamic Meetings --}}
                @foreach($meetings as $m)
                    <th class="text-center">P{{ $m->meeting_number }}</th>
                @endforeach

                <th class="text-center" width="4%">H</th>
                <th class="text-center" width="4%">S</th>
                <th class="text-center" width="4%">I</th>
                <th class="text-center" width="4%">TK</th>
                <th class="text-center" width="6%">%</th>
                <th class="text-center" width="8%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($report as $index => $data)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $data->id }}</td>
                <td>{{ $data->name }}</td>
                
                @foreach($meetings as $m)
                    <td class="text-center">{{ $data->per_meeting_status[$m->id] ?? '-' }}</td>
                @endforeach

                <td class="text-center">{{ $data->hadir }}</td>
                <td class="text-center">{{ $data->sakit }}</td>
                <td class="text-center">{{ $data->izin }}</td>
                <td class="text-center">{{ $data->alpha }}</td>
                <td class="text-center">{{ $data->percentage }}%</td>
                <td class="text-center">
                    @if($data->percentage >= 75)
                        <span class="badge-success">AMAN</span>
                    @else
                        <span class="badge-danger">PERINGATAN</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
