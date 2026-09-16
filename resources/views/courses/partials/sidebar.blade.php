<div class="space-y-6">
    <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
        <h3 class="font-black text-gray-800 text-[10px] uppercase tracking-[0.2em] mb-8 border-b pb-4">Informasi Kelas</h3>
        
        <div class="space-y-8">
            {{-- DOSEN --}}
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex-shrink-0 flex items-center justify-center overflow-hidden shadow-inner border border-gray-100">
                    @if($course->dosen && $course->dosen->avatar)
                        <img src="{{ asset('storage/' . $course->dosen->avatar) }}" alt="{{ $course->dosen->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-slate-400 font-black text-xs uppercase">{{ strtoupper(substr($course->dosen->name ?? 'DS', 0, 2)) }}</span>
                    @endif
                </div>
                <div class="flex flex-col">
                    <span class="text-[9px] text-gray-400 uppercase font-black tracking-widest mb-1">Dosen Pengampu</span>
                    <span class="font-black text-gray-900 text-sm leading-tight uppercase">{{ $course->dosen->name ?? '-' }}</span>
                    <span class="text-[10px] text-emerald-600 font-mono font-bold tracking-tighter italic mt-0.5">NIP: {{ $course->dosen->id ?? '-' }}</span>
                </div>
            </div>

            {{-- LABORAN --}}
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex-shrink-0 flex items-center justify-center overflow-hidden shadow-inner border border-gray-100">
                    @if($course->laboran && $course->laboran->avatar)
                        <img src="{{ asset('storage/' . $course->laboran->avatar) }}" alt="{{ $course->laboran->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-slate-400 font-black text-xs uppercase">
                            {{ $course->laboran ? strtoupper(substr($course->laboran->name, 0, 2)) : 'LB' }}
                        </span>
                    @endif
                </div>
                <div class="flex flex-col">
                    <span class="text-[9px] text-gray-400 uppercase font-black tracking-widest mb-1">Laboran</span>
                    <span class="font-black text-gray-900 text-sm leading-tight uppercase">{{ $course->laboran->name ?? '-' }}</span>
                </div>
            </div>

            {{-- ASLAB --}}
            <div class="flex items-center gap-5 pb-2">
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex-shrink-0 flex items-center justify-center overflow-hidden shadow-inner border border-emerald-100">
                    @if($course->aslab && $course->aslab->avatar)
                        <img src="{{ asset('storage/' . $course->aslab->avatar) }}" alt="{{ $course->aslab->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-emerald-600 font-black text-xs uppercase">{{ strtoupper(substr($course->aslab->name ?? 'AS', 0, 2)) }}</span>
                    @endif
                </div>
                <div class="flex flex-col">
                    <span class="text-[9px] text-gray-400 uppercase font-black tracking-widest mb-1">Asisten Laboratorium</span>
                    <span class="font-black text-emerald-700 text-sm leading-tight uppercase">{{ $course->aslab->name ?? '-' }}</span>
                </div>
            </div>
        </div>

        @if(strtoupper(auth()->user()->active_role) === 'MAHASISWA')
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
                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Statistik Kehadiran Kamu</h4>
                <div class="bg-gray-50 p-5 rounded-2xl border border-gray-100 shadow-inner">
                    <div class="flex justify-between items-end mb-3">
                        <span class="text-xs font-black text-gray-700 uppercase tracking-tight">Total Hadir</span>
                        <span class="text-2xl font-black {{ $textColor }} leading-none">{{ $attendancePercentage }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-3 overflow-hidden">
                        <div class="{{ $barColor }} h-2 rounded-full transition-all duration-1000" style="width: {{ $attendancePercentage }}%"></div>
                    </div>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest text-right">
                        {{ $hadirCount }} dari {{ $totalMeetings }} Pertemuan
                    </p>
                </div>
            </div>
        @else
            <div class="mt-8 pt-8 border-t border-gray-50 space-y-3">
                <a href="{{ route('attendance.report', $course->id) }}" class="w-full flex items-center justify-center gap-2 px-4 py-4 bg-emerald-50 text-emerald-800 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-100 transition border border-emerald-200 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 01-2-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Rekap Presensi
                </a>
            </div>
        @endif

        {{-- Daftar peserta hanya tersedia untuk pengelola kelas. --}}
        @if(in_array(strtoupper(auth()->user()->active_role), ['ASLAB', 'LABORAN', 'DOSEN']))
            <div class="mt-3">
                <a href="{{ route('courses.students', $course->id) }}" class="w-full flex items-center justify-center gap-2 px-4 py-4 bg-slate-50 text-slate-700 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition border border-slate-200 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-6-6zM13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Peserta Kelas
                </a>
            </div>
        @endif

        {{-- TOMBOL CETAK KARTU PRAKTIKUM (Khusus Mahasiswa) --}}
        @if(strtoupper(auth()->user()->active_role) === 'MAHASISWA')
            <div class="mt-3">
                <a href="{{ route('courses.print-card', $course->id) }}" target="_blank" class="w-full flex items-center justify-center gap-2 px-4 py-4 bg-amber-500 text-slate-900 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-400 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2z"></path></svg>
                    Cetak Kartu Praktikum
                </a>
            </div>
        @endif
    </div>
</div>
