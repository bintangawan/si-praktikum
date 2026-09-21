@if(!$course->semester->is_active)<p class="mb-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Kelas arsip hanya dapat dibaca. Aktifkan semester jika perubahan diperlukan.</p>@endif
{{-- Header & Navigasi --}}
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <a href="{{ route('courses.index') }}" class="text-emerald-600 hover:text-emerald-800 text-sm font-semibold flex items-center transition group">
        <svg class="w-4 h-4 mr-1 transform group-hover:-translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        KEMBALI KE DASHBOARD
    </a>

    @if($course->semester->is_active && in_array(strtoupper(Auth::user()->role), ['ASLAB', 'LABORAN']))
    <div class="flex flex-wrap gap-3">
        <a href="{{ route('courses.modules.edit', $course) }}" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Kelola modul</a>
        @if(auth()->user()->hasRole('Laboran'))
            <a href="{{ route('courses.edit', $course) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Edit kelas</a>
            <a href="{{ route('courses.staff.edit', $course) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm">Ganti penugasan</a>
        @endif
    </div>
    @endif
</div>
