<x-app-layout>
    <x-slot name="header_title">
        Daftar Kelas Praktikum
    </x-slot>

    <div class="max-w-[95rem] mx-auto py-8 px-4">
        
        {{-- Form Join Khusus Mahasiswa --}}
        @if(strtoupper(auth()->user()->role) === 'MAHASISWA')
        <div class="mb-8 bg-emerald-900 rounded-[2rem] p-8 text-white shadow-xl shadow-emerald-100 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20 blur-3xl pointer-events-none"></div>
            <div class="md:flex items-center justify-between relative z-10">
                <div>
                    <h3 class="text-2xl font-semibold tracking-tight mb-1">Gabung Kelas Baru</h3>
                    <p class="text-emerald-200 text-xs font-bold tracking-normal">Masukkan kode pendaftaran untuk mulai praktikum.</p>
                </div>
                <form action="{{ route('courses.enroll') }}" method="POST" class="mt-6 flex w-full flex-col gap-3 sm:flex-row md:mt-0 md:w-auto">
                    @csrf
                    <div class="w-full md:w-64">
                        <label for="enrollment_code" class="sr-only">Kode enrollment kelas</label>
                        <input id="enrollment_code" type="text" name="enrollment_code" value="{{ old('enrollment_code') }}"
                            class="w-full rounded-xl border-white/10 bg-white/10 px-5 py-3 font-semibold tracking-normal text-white placeholder-emerald-300 backdrop-blur-md focus:border-emerald-300 focus:ring-2 focus:ring-emerald-400"
                            placeholder="KODE KELAS..." maxlength="20" required>
                        <x-input-error :messages="$errors->get('enrollment_code')" class="mt-2 text-red-200" />
                    </div>
                    <button type="submit" class="w-full whitespace-nowrap rounded-xl bg-white px-7 py-3 text-xs font-semibold tracking-normal text-emerald-700 shadow-lg transition hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-white/20 active:scale-95 sm:w-auto">
                        Gabung ke Kelas
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- JIKA TIDAK ADA SEMESTER AKTIF --}}
        @if(!$activeSemester)
        <div class="bg-red-50 rounded-2xl shadow-sm border border-red-100 p-16 text-center">
            <div class="inline-flex p-6 bg-red-100 rounded-[2rem] text-red-400 mb-4">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-xl font-semibold text-red-800 tracking-tight mb-2">Sistem Sedang Ditangguhkan</h3>
            <p class="text-xs font-bold text-red-400 tracking-normal">Tidak ada semester akademik yang sedang aktif saat ini. Harap hubungi administrator.</p>
        </div>
        @else

        {{-- Header Konten --}}
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-6">
            <div>
                <h1 class="text-4xl font-semibold text-gray-800 tracking-tight leading-none mb-2">Daftar Praktikum</h1>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-xs font-semibold rounded-lg tracking-normal border border-emerald-100">Semester Berjalan</span>
                    <p class="text-xs font-bold text-gray-400 tracking-normal">{{ $activeSemester->name }}</p>
                </div>
            </div>
            
            @if(strtoupper(auth()->user()->role) === 'LABORAN')
            <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto">
                
                {{-- TOGGLE SWITCH FILTER KELAS (KHUSUS LABORAN) --}}
                @php
                    $currentView = request()->query('view', 'my_classes');
                @endphp
                <div class="flex p-1.5 bg-gray-100/80 rounded-2xl border border-gray-200">
                    <a href="{{ route('courses.index', ['view' => 'my_classes']) }}" 
                       class="px-5 py-2.5 rounded-xl text-xs font-semibold tracking-normal transition-all {{ $currentView === 'my_classes' ? 'bg-white text-emerald-600 shadow-sm border border-gray-100' : 'text-gray-400 hover:text-gray-600' }}">
                        Kelas Saya
                    </a>
                    <a href="{{ route('courses.index', ['view' => 'all']) }}" 
                       class="px-5 py-2.5 rounded-xl text-xs font-semibold tracking-normal transition-all {{ $currentView === 'all' ? 'bg-white text-emerald-600 shadow-sm border border-gray-100' : 'text-gray-400 hover:text-gray-600' }}">
                        Semua Kelas
                    </a>
                </div>

                {{-- Tombol Buat Kelas --}}
                <a href="{{ route('courses.create') }}" class="w-full sm:w-auto bg-slate-900 hover:bg-slate-800 text-white px-6 py-3.5 rounded-2xl text-xs font-semibold tracking-normal transition shadow-lg active:scale-95 flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Buat Kelas
                </a>
            </div>
            @endif
        </div>

        {{-- Grid Kartu Kelas --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
            @forelse($courses as $course)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl hover:border-gray-200 transition-all duration-300 group flex flex-col relative h-full">
                
                {{-- Decorative Blob --}}
                <div class="absolute -top-6 -right-10 w-32 h-32 bg-emerald-50 rounded-full blur-3xl opacity-50 group-hover:opacity-100 transition-opacity"></div>

                <div class="p-8 flex-1 relative z-10">
                    <div class="flex justify-between items-start mb-6">
                        <div class="w-14 h-14 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-500 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300 border border-emerald-100">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <span class="text-xs font-semibold bg-gray-50 text-gray-500 px-3 py-1.5 rounded-xl border border-gray-100 tracking-normal shadow-sm">
                            {{ $course->class_group }}
                        </span>
                    </div>

                    <h3 class="text-2xl font-semibold text-gray-800 leading-tight mb-2 truncate" title="{{ $course->course_name }}">
                        {{ $course->course_name }}
                    </h3>
                    
                    <div class="space-y-3 border-t border-gray-50 pt-6">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-500 border border-emerald-100">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div class="flex flex-1 items-center justify-between text-xs font-bold">
                                <span class="uppercase tracking-normal text-gray-400">Mahasiswa</span>
                                <span class="rounded-lg bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-600">{{ $course->students_count }} Orang</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 text-xs font-semibold border border-gray-100">DS</div>
                            <div class="flex-1 flex justify-between items-center text-xs font-bold">
                                <span class="text-gray-400 tracking-normal">Dosen</span>
                                <span class="text-gray-700 truncate max-w-[150px] text-right">{{ $course->dosen->name }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 text-xs font-semibold border border-gray-100">LB</div>
                            <div class="flex-1 flex justify-between items-center text-xs font-bold">
                                <span class="text-gray-400 tracking-normal">Laboran</span>
                                <span class="text-gray-700 truncate max-w-[150px] text-right">{{ $course->laboran->name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-gray-50 flex items-center justify-center text-gray-400 text-xs font-semibold border border-gray-100">AL</div>
                            <div class="flex-1 flex justify-between items-center text-xs font-bold">
                                <span class="text-gray-400 tracking-normal">Aslab</span>
                                <span class="text-gray-700 truncate max-w-[150px] text-right">{{ $course->aslab->name }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Tampilkan Kode Enrollment HANYA untuk Non-Mahasiswa --}}
                    @if(strtoupper(auth()->user()->role) !== 'MAHASISWA')
                    <div class="mt-8 p-4 bg-emerald-50/50 rounded-2xl border border-emerald-100/50 text-center relative group/code overflow-hidden">
                        <div class="absolute inset-0 bg-emerald-100/0 group-hover/code:bg-emerald-100/50 transition"></div>
                        <p class="text-xs text-emerald-400 font-semibold tracking-normal relative z-10 mb-1">Enrollment Code</p>
                        <p class="relative z-10 select-all text-xl font-semibold tracking-normal text-emerald-700">{{ $course->enrollment_code }}</p>
                    </div>
                    @endif
                </div>

                <div class="relative z-10 mt-auto grid gap-3 px-8 pb-8 pt-4 {{ in_array(strtoupper(auth()->user()->role), ['ASLAB', 'LABORAN', 'DOSEN']) ? 'sm:grid-cols-2' : '' }}">
                    @if(in_array(strtoupper(auth()->user()->role), ['ASLAB', 'LABORAN', 'DOSEN']))
                    <a href="{{ route('courses.students', $course) }}" class="block w-full rounded-2xl border border-emerald-100 bg-emerald-50 py-4 text-center text-xs font-semibold tracking-normal text-emerald-700 shadow-sm transition hover:bg-emerald-100 active:scale-95">
                        {{ strtoupper(auth()->user()->role) === 'LABORAN' ? 'Kelola Mahasiswa' : 'Lihat Mahasiswa' }}
                    </a>
                    @endif
                    <a href="{{ route('courses.show', $course) }}" class="w-full block text-center bg-gray-50 hover:bg-slate-900 text-gray-500 hover:text-white text-xs font-semibold py-4 rounded-2xl tracking-normal transition-all border border-gray-100 shadow-sm hover:shadow-xl active:scale-95">
                        Buka Kelas
                    </a>
                    @if(auth()->user()->hasRole('Laboran'))
                        <a href="{{ route('courses.edit', $course) }}" class="block w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 text-center text-xs font-semibold text-slate-700 transition hover:bg-slate-100">Edit kelas</a>
                        <form action="{{ route('courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Hapus kelas {{ addslashes($course->course_name) }}? Modul dan daftar peserta pada kelas kosong ini akan ikut dihapus.')">
                            @csrf
                            @method('DELETE')
                            <button class="w-full rounded-2xl border border-red-100 bg-red-50 py-3 text-xs font-semibold text-red-700 transition hover:bg-red-100">Hapus kelas</button>
                        </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-span-full py-24 bg-white rounded-2xl border border-gray-100 flex flex-col items-center justify-center text-center shadow-sm">
                <div class="bg-gray-50 p-6 rounded-[2rem] mb-6">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 tracking-tight mb-2">Belum Ada Kelas</h3>
                <p class="text-xs font-bold text-gray-400 tracking-normal max-w-md">Tidak ada kelas praktikum yang terdaftar atau diikuti pada semester aktif ini.</p>
            </div>
            @endforelse
        </div>
        @if(method_exists($courses, 'links') && $courses->hasPages())
            <div class="mt-8">{{ $courses->links() }}</div>
        @endif
        @endif
    </div>
</x-app-layout>
