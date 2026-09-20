<x-app-layout>
    @if(!$meeting->course->semester->is_active)<p class="mb-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Kelas arsip hanya dapat dibaca.</p>@endif
    <x-slot name="header_title">
        Presensi: {{ $meeting->title }}
    </x-slot>

    <div class="mb-6">
        <a href="{{ route('courses.show', $meeting->course) }}" class="text-emerald-600 hover:text-emerald-800 text-sm font-semibold flex items-center transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Kembali ke Detail Kelas
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-gray-800 text-lg">Daftar Hadir Mahasiswa</h3>
                <p class="text-xs text-gray-500 tracking-normal font-semibold mt-1">
                    Pertemuan {{ $meeting->meeting_number }} &bull; Status: 
                    @if($existingAttendances->count() > 0)
                        <span class="text-emerald-600">Terisi</span>
                    @else
                        <span class="text-amber-600">Belum Terisi</span>
                    @endif
                </p>
            </div>
        </div>

        <form action="{{ route('attendance.store', $meeting->id) }}" method="POST">
            @csrf
                    <fieldset class="contents" @disabled(!$meeting->course->semester->is_active)>
            <div class="overflow-x-auto">
                <table class="min-w-[680px] w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-xs tracking-normal text-gray-400 font-semibold border-b border-gray-100">
                            <th class="px-6 py-4">Mahasiswa</th>
                            <th class="px-6 py-4 text-center">Hadir</th>
                            <th class="px-6 py-4 text-center">Sakit</th>
                            <th class="px-6 py-4 text-center">Izin</th>
                            <th class="px-6 py-4 text-center">Tanpa Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($meeting->course->students as $student)
                        @php
                            // Ambil status dari database jika ada, default ke 'Hadir' jika belum ada
                            $savedStatus = $existingAttendances[$student->id]->status ?? 'Hadir';
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-800 text-sm">{{ $student->name }}</span>
                                    <span class="text-xs text-gray-400">{{ $student->id }}</span>
                                </div>
                            </td>
                            
                            {{-- Radio Buttons Logic --}}
                            @foreach(['Hadir' => 'emerald', 'Sakit' => 'blue', 'Izin' => 'amber', 'Tanpa Keterangan' => 'red'] as $status => $color)
                            <td class="px-6 py-4 text-center">
                                <input type="radio" 
                                       name="attendances[{{ $student->id }}]" 
                                       value="{{ $status }}"
                                       {{ $savedStatus == $status ? 'checked' : '' }}
                                       class="w-4 h-4 text-{{ $color }}-600 focus:ring-{{ $color }}-500 border-gray-300">
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-400 italic text-sm">
                                Belum ada mahasiswa di kelas ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end">
                @if($existingAttendances->count() > 0)
                    {{-- Tombol Edit jika data sudah ada --}}
                    <button type="submit" class="bg-amber-500 text-white px-8 py-2.5 rounded-lg font-bold text-sm hover:bg-amber-600 transition shadow-lg flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Perbarui Presensi
                    </button>
                @else
                    {{-- Tombol Simpan jika data baru --}}
                    <button type="submit" class="bg-emerald-600 text-white px-8 py-2.5 rounded-lg font-bold text-sm hover:bg-emerald-700 transition shadow-lg flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Simpan Presensi
                    </button>
                @endif
            </div>
        </fieldset></form>
    </div>
</x-app-layout>
