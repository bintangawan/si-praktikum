@if($course->finalTask)
<div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-[2rem] shadow-xl shadow-indigo-100 p-8 mb-8 relative overflow-hidden text-white">
    <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
    
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative z-10">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-3">
                <span class="px-3 py-1 bg-white/20 backdrop-blur-md text-white text-[9px] font-black rounded-lg uppercase tracking-widest border border-white/30">TUGAS PUNCAK</span>
                <h3 class="text-2xl font-black uppercase tracking-tight">Laporan Praktikum Final</h3>
            </div>
            <p class="text-indigo-100 text-sm font-medium max-w-2xl leading-relaxed">{{ $course->finalTask->description }}</p>
            
            @if($course->finalTask->deadline)
                <div class="mt-4 flex items-center gap-2 text-[10px] font-black uppercase tracking-widest {{ now()->gt($course->finalTask->deadline) ? 'text-red-300 font-extrabold' : 'text-indigo-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Batas Waktu: {{ \Carbon\Carbon::parse($course->finalTask->deadline)->format('d M Y, H:i') }} 
                    {{ now()->gt($course->finalTask->deadline) ? '(PENGUMPULAN DITUTUP)' : '' }}
                </div>
            @endif
        </div>

        <div class="w-full md:w-auto">
            @if(strtoupper(Auth::user()->role) === 'MAHASISWA')
                <a href="{{ route('mahasiswa.final-tasks.manage', $course->finalTask->id) }}" class="block w-full md:w-auto px-8 py-4 bg-white text-indigo-600 text-[11px] font-black rounded-2xl uppercase tracking-widest hover:bg-indigo-50 transition shadow-lg text-center active:scale-95">
                    Kelola Laprak Final
                </a>
            @else
                <a href="{{ route('final-tasks.index', $course->finalTask->id) }}" class="block w-full md:w-auto px-8 py-4 bg-slate-900 text-white text-[11px] font-black rounded-2xl uppercase tracking-widest hover:bg-slate-800 transition shadow-lg text-center border border-white/10 active:scale-95">
                    Review Pengumpulan
                </a>
            @endif
        </div>
    </div>

    @if(strtoupper(auth()->user()->role) === 'MAHASISWA')
        @php 
            $finalSub = \App\Models\Submission::where('final_task_id', $course->finalTask->id)
                            ->where('student_id', auth()->id())
                            ->where('is_final', true)
                            ->first(); 
            $isPastDeadline = $course->finalTask->deadline && now()->gt(\Carbon\Carbon::parse($course->finalTask->deadline));
            $isCompleted = $finalSub && strtoupper($finalSub->aslab_status) == 'ACC' && strtoupper($finalSub->laboran_status) == 'ACC' && strtoupper($finalSub->dosen_status) == 'ACC';
        @endphp
        
        <div class="mt-6 border-t border-white/10 pt-5 space-y-2.5 relative z-10">
            @if($finalSub)
                <div class="flex items-center gap-3 text-[9px] font-black uppercase tracking-tighter">
                    <span class="text-indigo-300 w-24 text-[8px]">Status Aslab:</span>
                    <span class="px-2 py-0.5 rounded-md {{ $finalSub->aslab_status == 'ACC' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : ($finalSub->aslab_status == 'REVISI' ? 'bg-red-500/20 text-red-300 border border-red-500/30 animate-pulse' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30') }}">
                        {{ $finalSub->aslab_status }}
                    </span>
                    @if($finalSub->aslab_status == 'ACC' && $finalSub->aslab_acc_at)
                        <span class="text-indigo-300 text-[8px] border-l border-indigo-400/50 pl-3">{{ \Carbon\Carbon::parse($finalSub->aslab_acc_at)->format('d M Y') }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-3 text-[9px] font-black uppercase tracking-tighter">
                    <span class="text-indigo-300 w-24 text-[8px]">Status Laboran:</span>
                    <span class="px-2 py-0.5 rounded-md {{ $finalSub->laboran_status == 'ACC' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : ($finalSub->laboran_status == 'REVISI' ? 'bg-red-500/20 text-red-300 border border-red-500/30 animate-pulse' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30') }}">
                        {{ $finalSub->laboran_status }}
                    </span>
                    @if($finalSub->laboran_status == 'ACC' && $finalSub->laboran_acc_at)
                        <span class="text-indigo-300 text-[8px] border-l border-indigo-400/50 pl-3">{{ \Carbon\Carbon::parse($finalSub->laboran_acc_at)->format('d M Y') }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-3 text-[9px] font-black uppercase tracking-tighter">
                    <span class="text-indigo-300 w-24 text-[8px]">Status Dosen:</span>
                    <span class="px-2 py-0.5 rounded-md {{ $finalSub->dosen_status == 'ACC' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : ($finalSub->dosen_status == 'REVISI' ? 'bg-red-500/20 text-red-300 border border-red-500/30 animate-pulse' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30') }}">
                        {{ $finalSub->dosen_status }}
                    </span>
                    @if($finalSub->dosen_status == 'ACC' && $finalSub->dosen_acc_at)
                        <span class="text-indigo-300 text-[8px] border-l border-indigo-400/50 pl-3">{{ \Carbon\Carbon::parse($finalSub->dosen_acc_at)->format('d M Y') }}</span>
                    @endif
                </div>

                @if($isPastDeadline && !$isCompleted)
                    <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-red-500/20 text-red-200 text-[9px] font-black rounded-lg uppercase tracking-widest border border-red-500/30 backdrop-blur-sm">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Lewat Deadline & Belum ACC Sepenuhnya
                    </div>
                @endif
            @else
                @if($isPastDeadline)
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-500/20 text-red-200 text-[9px] font-black rounded-lg uppercase tracking-widest border border-red-500/30 backdrop-blur-sm">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Waktu Habis (Belum Mengumpulkan)
                    </div>
                @else
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-500/20 text-amber-200 text-[9px] font-black rounded-lg uppercase tracking-widest border border-amber-500/30 backdrop-blur-sm">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Belum Mengumpulkan Laporan Final
                    </div>
                @endif
            @endif
        </div>
    @endif
</div>
@endif
