<div class="space-y-6">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
        <h3 class="font-semibold text-gray-800 text-xs tracking-normal mb-8 border-b pb-4">Informasi Kelas</h3>
        
        <div class="space-y-8">
            {{-- DOSEN --}}
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex-shrink-0 flex items-center justify-center overflow-hidden shadow-inner border border-gray-100">
                    @if($course->dosen && $course->dosen->avatar)
                        <img src="{{ asset('storage/' . $course->dosen->avatar) }}" alt="{{ $course->dosen->name }}" loading="lazy" class="w-full h-full object-cover">
                    @else
                        <span class="text-slate-400 font-semibold text-xs">{{ strtoupper(substr($course->dosen->name ?? 'DS', 0, 2)) }}</span>
                    @endif
                </div>
                <div class="flex flex-col">
                    <span class="text-xs text-gray-400 font-semibold tracking-normal mb-1">Dosen Pengampu</span>
                    <span class="font-semibold text-gray-900 text-sm leading-tight">{{ $course->dosen->name ?? '-' }}</span>
                    <span class="mt-0.5 text-xs font-bold italic tracking-tighter text-emerald-700">NIP: {{ $course->dosen->id ?? '-' }}</span>
                </div>
            </div>

            {{-- LABORAN --}}
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex-shrink-0 flex items-center justify-center overflow-hidden shadow-inner border border-gray-100">
                    @if($course->laboran && $course->laboran->avatar)
                        <img src="{{ asset('storage/' . $course->laboran->avatar) }}" alt="{{ $course->laboran->name }}" loading="lazy" class="w-full h-full object-cover">
                    @else
                        <span class="text-slate-400 font-semibold text-xs">
                            {{ $course->laboran ? strtoupper(substr($course->laboran->name, 0, 2)) : 'LB' }}
                        </span>
                    @endif
                </div>
                <div class="flex flex-col">
                    <span class="text-xs text-gray-400 font-semibold tracking-normal mb-1">Laboran</span>
                    <span class="font-semibold text-gray-900 text-sm leading-tight">{{ $course->laboran->name ?? '-' }}</span>
                </div>
            </div>

            {{-- ASLAB --}}
            <div class="flex items-center gap-5 pb-2">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex-shrink-0 flex items-center justify-center overflow-hidden shadow-inner border border-emerald-100">
                    @if($course->aslab && $course->aslab->avatar)
                        <img src="{{ asset('storage/' . $course->aslab->avatar) }}" alt="{{ $course->aslab->name }}" loading="lazy" class="w-full h-full object-cover">
                    @else
                        <span class="text-emerald-600 font-semibold text-xs">{{ strtoupper(substr($course->aslab->name ?? 'AS', 0, 2)) }}</span>
                    @endif
                </div>
                <div class="flex flex-col">
                    <span class="text-xs text-gray-400 font-semibold tracking-normal mb-1">Asisten Laboratorium</span>
                    <span class="font-semibold text-emerald-700 text-sm leading-tight">{{ $course->aslab->name ?? '-' }}</span>
                </div>
            </div>
        </div>

        @if(strtoupper(auth()->user()->role) === 'MAHASISWA')
            @php
                $conductedMeetings = $course->meetings->filter(fn($meeting) => $meeting->attendances_count > 0);
                $totalMeetings = $conductedMeetings->count();
                $hadirCount = 0;
                foreach($conductedMeetings as $mtg) {
                    $att = $mtg->attendances->where('student_id', auth()->id())->first();
                    if($att && in_array(strtoupper($att->status), ['HADIR', 'H'])) {
                        $hadirCount++;
                    }
                }
                $attendancePercentage = $totalMeetings > 0 ? round(($hadirCount / $totalMeetings) * 100) : 0;
                
                $barColor = $attendancePercentage >= 75 ? 'bg-emerald-500' : ($attendancePercentage >= 50 ? 'bg-amber-500' : 'bg-red-500');
                $textColor = $attendancePercentage >= 75 ? 'text-emerald-600' : ($attendancePercentage >= 50 ? 'text-amber-600' : 'text-red-600');
            @endphp
            
            <div class="mt-8 pt-8 border-t border-gray-50">
                <h4 class="text-xs font-semibold text-gray-400 tracking-normal mb-4">Statistik Kehadiran Kamu</h4>
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 shadow-inner">
                    <div class="flex justify-between items-end mb-3">
                        <span class="text-xs font-semibold text-gray-700 tracking-tight">Total Hadir</span>
                        <span class="text-2xl font-semibold {{ $textColor }} leading-none">{{ $attendancePercentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-3 overflow-hidden">
                        <div class="{{ $barColor }} h-2 rounded-full transition-all duration-1000" style="width: {{ $attendancePercentage }}%"></div>
                    </div>
                    <p class="text-xs font-bold text-gray-400 tracking-normal text-right">
                        {{ $hadirCount }} dari {{ $totalMeetings }} Pertemuan
                    </p>
                </div>
            </div>
        @else
            <div class="mt-8 pt-8 border-t border-gray-50 space-y-3">
                <a href="{{ route('attendance.report', $course) }}" class="w-full flex items-center justify-center gap-2 px-4 py-4 bg-emerald-50 text-emerald-800 rounded-2xl text-xs font-semibold tracking-normal hover:bg-emerald-100 transition border border-emerald-200 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Rekap Presensi
                </a>
            </div>
        @endif

        {{-- Daftar peserta hanya tersedia untuk pengelola kelas. --}}
        @if(in_array(strtoupper(auth()->user()->role), ['ASLAB', 'LABORAN', 'DOSEN']))
            <div class="mt-3">
                <a href="{{ route('courses.students', $course) }}" class="w-full flex items-center justify-center gap-2 px-4 py-4 bg-slate-50 text-slate-700 rounded-2xl text-xs font-semibold tracking-normal hover:bg-slate-100 transition border border-slate-200 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-6-6zM13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Peserta Kelas
                </a>
            </div>
        @endif

        {{-- TOMBOL CETAK KARTU PRAKTIKUM (Khusus Mahasiswa) --}}
        @if(strtoupper(auth()->user()->role) === 'MAHASISWA')
            <div class="mt-3">
                <a href="{{ route('courses.print-card', $course) }}" target="_blank" class="w-full flex items-center justify-center gap-2 px-4 py-4 bg-amber-500 text-slate-900 rounded-2xl text-xs font-semibold tracking-normal hover:bg-amber-400 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2z"></path></svg>
                    Cetak Kartu Praktikum
                </a>
            </div>
        @endif
    </div>
</div>
