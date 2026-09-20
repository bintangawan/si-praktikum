<x-app-layout>
    <x-slot name="header_title">
        Rekap Presensi: {{ $course->course_name }}
    </x-slot>

    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <a href="{{ route('courses.show', $course) }}" class="text-emerald-600 hover:text-emerald-800 text-sm font-semibold flex items-center transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke Detail Kelas
        </a>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <a href="{{ route('attendance.report.pdf', $course) }}" class="flex-1 sm:flex-none bg-red-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-red-700 transition flex items-center justify-center shadow-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export PDF
            </a>

            <a href="{{ route('attendance.report.excel', $course) }}" class="flex-1 sm:flex-none bg-emerald-600 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-emerald-700 transition flex items-center justify-center shadow-sm gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-xs tracking-normal text-gray-400 font-semibold border-b border-gray-100">
                        <th class="px-6 py-4 min-w-[200px]">Mahasiswa</th>
                        
                        {{-- KOLOM DINAMIS PER-PERTEMUAN --}}
                        @foreach($meetings as $meeting)
                            <th class="px-3 py-4 text-center whitespace-nowrap" title="{{ $meeting->title }}">
                                P{{ $meeting->meeting_number }}
                            </th>
                        @endforeach

                        <th class="px-4 py-4 text-center bg-gray-100/50">H</th>
                        <th class="px-4 py-4 text-center bg-gray-100/50">S</th>
                        <th class="px-4 py-4 text-center bg-gray-100/50">I</th>
                        <th class="px-4 py-4 text-center bg-gray-100/50">TK</th>
                        <th class="px-6 py-4 text-center">Persentase</th>
                        <th class="px-6 py-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($report as $data)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-800 text-sm">{{ $data->name }}</span>
                                <span class="text-xs text-gray-400">{{ $data->id }}</span>
                            </div>
                        </td>

                        {{-- STATUS SETIAP PERTEMUAN --}}
                        @foreach($meetings as $meeting)
                            @php
                                $code = $data->per_meeting_status[$meeting->id] ?? '-';
                                if ($code === 'A') { $code = 'TK'; } // Fallback dari kode A ke TK
                                
                                $badgeClass = match($code) {
                                    'H'  => 'bg-emerald-100 text-emerald-700',
                                    'S'  => 'bg-amber-100 text-amber-700',
                                    'I'  => 'bg-amber-100 text-amber-700',
                                    'TK' => 'bg-red-100 text-red-700',
                                    default => 'bg-gray-100 text-gray-400'
                                };
                            @endphp
                            <td class="px-2 py-4 text-center">
                                <span class="w-6 h-6 inline-flex items-center justify-center rounded-md text-xs font-semibold {{ $badgeClass }}">
                                    {{ $code }}
                                </span>
                            </td>
                        @endforeach

                        {{-- REKAP AKHIR --}}
                        <td class="px-4 py-4 text-center text-sm font-bold text-emerald-600 bg-gray-50/30">{{ $data->hadir }}</td>
                        <td class="px-4 py-4 text-center text-sm font-bold text-amber-700 bg-gray-50/30">{{ $data->sakit }}</td>
                        <td class="px-4 py-4 text-center text-sm font-bold text-amber-600 bg-gray-50/30">{{ $data->izin }}</td>
                        <td class="px-4 py-4 text-center text-sm font-bold text-red-600 bg-gray-50/30">{{ $data->alpha }}</td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-12 bg-gray-100 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full {{ $data->percentage >= 75 ? 'bg-emerald-500' : 'bg-red-500' }}" style="width: {{ $data->percentage }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-gray-700">{{ $data->percentage }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($data->percentage >= 75)
                                <span class="px-2 py-1 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded">Aman</span>
                            @else
                                <span class="px-2 py-1 bg-red-50 text-red-700 text-xs font-semibold rounded">Peringatan</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
