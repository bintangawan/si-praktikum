<x-app-layout>
    <x-slot name="header_title">Arsip Praktikum</x-slot>

    <div class="max-w-[95rem] mx-auto py-0 px-4">
        
        {{-- HEADER SECTION --}}
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] hover:text-indigo-600 transition group mb-3">
                    <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Dashboard Aktif
                </a>
                <h2 class="text-3xl font-black text-gray-800 tracking-tight uppercase leading-none">Arsip Praktikum</h2>
                <p class="text-[11px] font-bold text-gray-400 mt-2 uppercase tracking-widest">Data historis dari semester sebelumnya</p>
            </div>
        </div>

        @if($courses->isEmpty())
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-16 text-center">
                <div class="inline-flex p-6 bg-slate-50 rounded-[2rem] text-slate-300 mb-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Belum ada data praktikum yang diarsipkan.</p>
            </div>
        @else
            {{-- LOOPING PER SEMESTER --}}
            <div class="space-y-12">
                @foreach($courses as $semesterName => $semesterCourses)
                    <div>
                        {{-- Nama Semester sebagai Pemisah --}}
                        <div class="flex items-center gap-4 mb-6">
                            <h3 class="text-lg font-black text-gray-600 uppercase tracking-widest">{{ $semesterName }}</h3>
                            <div class="h-px bg-gray-200 flex-1"></div>
                        </div>

                        {{-- Grid Card Kelas --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                            @foreach($semesterCourses as $course)
                                {{-- Gunakan gaya card yang sama dengan dashboard, tapi dengan nuansa grayscale/redup --}}
                                <a href="{{ route('courses.show', $course->id) }}" class="block group">
                                    <div class="bg-white rounded-[2.5rem] border border-gray-200 p-8 shadow-sm hover:shadow-lg hover:border-gray-300 transition-all relative overflow-hidden h-full flex flex-col grayscale hover:grayscale-0">
                                        
                                        {{-- Badge Arsip --}}
                                        <div class="absolute top-0 right-0 bg-gray-100 text-gray-400 text-[8px] font-black uppercase tracking-widest px-4 py-1.5 rounded-bl-xl border-b border-l border-gray-200">
                                            Diarsipkan
                                        </div>

                                        {{-- Header Card --}}
                                        <div class="flex justify-between items-start mb-6">
                                            <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-indigo-50 group-hover:text-indigo-500 transition-colors border border-gray-100">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                            </div>
                                        </div>

                                        {{-- Judul --}}
                                        <div class="flex-1">
                                            <h3 class="text-xl font-black text-gray-700 group-hover:text-gray-900 leading-tight mb-2 uppercase">{{ $course->course_name }}</h3>
                                        </div>

                                        {{-- Info Personal --}}
                                        <div class="mt-8 pt-6 border-t border-gray-100 space-y-3">
                                            @if(strtoupper(auth()->user()->role) === 'MAHASISWA')
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-[8px] font-black uppercase">DS</div>
                                                    <div>
                                                        <p class="text-[8px] text-gray-400 font-bold uppercase tracking-widest">Dosen</p>
                                                        <p class="text-xs font-black text-gray-600 uppercase">{{ $course->dosen->name }}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-[8px] font-black uppercase">AL</div>
                                                    <div>
                                                        <p class="text-[8px] text-gray-400 font-bold uppercase tracking-widest">Aslab</p>
                                                        <p class="text-xs font-black text-gray-600 uppercase">{{ $course->aslab->name }}</p>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-gray-400">
                                                    <span>Total Mahasiswa</span>
                                                    <span class="px-2.5 py-1 bg-gray-100 rounded-md text-gray-600">{{ $course->students->count() }} Orang</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
