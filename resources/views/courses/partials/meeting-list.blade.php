<div class="lg:col-span-2">
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
            <h3 class="font-black text-gray-800 text-[10px] uppercase tracking-[0.2em]">Daftar Tugas & Pertemuan</h3>
        </div>

        <div class="divide-y divide-gray-50">
            @forelse($course->meetings->sortBy('meeting_number') as $meeting)
                @php 
                    $sub = $meeting->submissions->where('student_id', auth()->id())->first(); 
                @endphp
                
                <div class="p-8 hover:bg-gray-50/50 transition group">
                    <div class="flex flex-col md:flex-row justify-between gap-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-3 py-1 bg-slate-800 text-white text-[9px] font-black rounded-lg uppercase tracking-[0.1em]">
                                    P{{ $meeting->meeting_number }}
                                </span>
                                
                                @if(strtoupper(auth()->user()->role) === 'MAHASISWA')
                                    @php 
                                        $attendance = $meeting->attendances->where('student_id', auth()->id())->first(); 
                                        $anyAbsen = $meeting->attendances_count > 0;
                                        
                                        if ($attendance) {
                                            $attStatus = strtoupper($attendance->status);
                                            $attConfig = match($attStatus) {
                                                'HADIR'          => ['color' => 'text-emerald-500', 'label' => '● Hadir'],
                                                'IZIN', 'SAKIT'  => ['color' => 'text-amber-500', 'label' => '● ' . ucfirst(strtolower($attStatus))],
                                                'ALPA', 'ALPHA'  => ['color' => 'text-red-500', 'label' => '● Alpa'],
                                                default          => ['color' => 'text-gray-300', 'label' => '○ Belum Presensi'],
                                            };
                                        } else {
                                            if ($anyAbsen) {
                                                $attConfig = ['color' => 'text-red-500', 'label' => '● Alpa'];
                                            } else {
                                                $attConfig = ['color' => 'text-gray-300', 'label' => '○ Belum Presensi'];
                                            }
                                        }
                                    @endphp
                                    <span class="text-[9px] font-black uppercase tracking-widest {{ $attConfig['color'] }}">
                                        {{ $attConfig['label'] }}
                                    </span>
                                @endif
                            </div>
                            
                            <h4 class="font-black text-gray-800 text-xl leading-tight group-hover:text-indigo-600 transition">{{ $meeting->title }}</h4>
                            
                            {{-- TAMPILAN DEADLINE DITAMBAHKAN DI SINI --}}
                            @if($meeting->deadline)
                                <div class="mt-2 flex items-center gap-1.5 text-[9px] font-black uppercase tracking-widest {{ now()->gt(\Carbon\Carbon::parse($meeting->deadline)) ? 'text-red-500' : 'text-gray-400' }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Batas Waktu: {{ \Carbon\Carbon::parse($meeting->deadline)->format('d M Y - H:i') }} WIB
                                </div>
                            @endif
                            
                            @if(strtoupper(auth()->user()->role) === 'MAHASISWA')
                                @php 
                                    $isPastDeadline = $meeting->deadline && now()->gt(\Carbon\Carbon::parse($meeting->deadline));
                                    $isCompleted = $sub && strtoupper($sub->aslab_status) == 'ACC' && strtoupper($sub->laboran_status) == 'ACC';
                                @endphp
                                
                                <div class="mt-5 space-y-2.5">
                                    @if($sub)
                                        <div class="flex items-center gap-3 text-[9px] font-black uppercase tracking-tighter">
                                            <span class="text-gray-400 w-24 text-[8px]">Status Aslab:</span>
                                            <span class="px-2 py-0.5 rounded-md {{ $sub->aslab_status == 'ACC' ? 'bg-emerald-50 text-emerald-600' : ($sub->aslab_status == 'REVISI' ? 'bg-red-50 text-red-600 animate-pulse' : 'bg-amber-50 text-amber-600') }}">
                                                {{ $sub->aslab_status }}
                                            </span>
                                            @if($sub->aslab_status == 'ACC' && $sub->aslab_acc_at)
                                                <span class="text-gray-400 text-[8px] border-l border-gray-200 pl-3">{{ \Carbon\Carbon::parse($sub->aslab_acc_at)->format('d M Y') }}</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-3 text-[9px] font-black uppercase tracking-tighter">
                                            <span class="text-gray-400 w-24 text-[8px]">Status Laboran:</span>
                                            <span class="px-2 py-0.5 rounded-md {{ $sub->laboran_status == 'ACC' ? 'bg-emerald-50 text-emerald-600' : ($sub->laboran_status == 'REVISI' ? 'bg-red-50 text-red-600 animate-pulse' : 'bg-amber-50 text-amber-600') }}">
                                                {{ $sub->laboran_status }}
                                            </span>
                                            @if($sub->laboran_status == 'ACC' && $sub->laboran_acc_at)
                                                <span class="text-gray-400 text-[8px] border-l border-gray-200 pl-3">{{ \Carbon\Carbon::parse($sub->laboran_acc_at)->format('d M Y') }}</span>
                                            @endif
                                        </div>

                                        @if($isPastDeadline && !$isCompleted)
                                            <div class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 text-[9px] font-black rounded-lg uppercase tracking-widest border border-red-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                Lewat Deadline & Belum ACC Sepenuhnya
                                            </div>
                                        @endif
                                    @else
                                        @if($isPastDeadline)
                                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-600 text-[9px] font-black rounded-lg uppercase tracking-widest border border-red-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Waktu Habis (Belum Mengumpulkan)
                                            </div>
                                        @else
                                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-50 text-amber-600 text-[9px] font-black rounded-lg uppercase tracking-widest border border-amber-100">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Belum Mengumpulkan Tugas
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            @if(in_array(strtoupper(auth()->user()->role), ['DOSEN', 'ASLAB', 'LABORAN']))
                                <a href="{{ route('attendance.index', $meeting->id) }}" class="px-5 py-3 bg-amber-50 text-amber-700 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-amber-100 hover:bg-amber-100 transition shadow-sm text-center min-w-[100px]">
                                    Presensi
                                </a>
                                <a href="{{ route('submissions.index', $meeting->id) }}" class="px-5 py-3 bg-slate-800 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-900 transition flex items-center shadow-lg shadow-slate-200">
                                    Tugas ({{ $meeting->submissions->count() }})
                                </a>
                            @endif

                            @if(strtoupper(auth()->user()->role) === 'MAHASISWA')
                                <a href="{{ route('mahasiswa.submissions.manage', $meeting->id) }}" 
                                   class="px-5 py-3 {{ $sub ? ($sub->aslab_status == 'REVISI' ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700') : 'bg-indigo-600 hover:bg-indigo-700' }} text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl transition active:scale-95 text-center min-w-[120px]">
                                    {{ $sub ? 'Kelola Tugas' : 'Kumpul Tugas' }}
                                </a>
                            @endif
                            
                            @if($meeting->module_drive_link)
                                <a href="{{ $meeting->module_drive_link }}" target="_blank" class="p-3 bg-gray-50 text-gray-400 rounded-2xl border border-gray-100 hover:text-indigo-600 hover:bg-white transition" title="Download Modul">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-24 text-center">
                    <div class="inline-flex p-6 bg-slate-50 rounded-[2rem] text-slate-300 mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <p class="text-gray-400 font-black text-[10px] uppercase tracking-[0.2em]">Belum Ada Data Pertemuan</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
