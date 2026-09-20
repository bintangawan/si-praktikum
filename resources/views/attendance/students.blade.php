<x-app-layout>
    <x-slot name="header_title">Daftar Peserta: {{ $course->course_name }}</x-slot>

    <div class="max-w-[95rem] mx-auto py-0 px-4">
        {{-- HEADER SECTION --}}
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <a href="{{ route('courses.show', $course) }}"
                   class="inline-flex items-center text-xs font-semibold text-gray-400 tracking-normal hover:text-emerald-600 transition group mb-3">
                    <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Detail Kelas
                </a>
                <h2 class="text-3xl font-semibold text-gray-800 tracking-tight leading-none">Daftar Peserta</h2>
                <p class="text-xs font-bold text-gray-400 mt-2 tracking-normal">{{ $course->course_name }} ({{ $course->class_group }})</p>
            </div>
            
            <div class="bg-white px-6 py-4 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="p-3 bg-emerald-50 text-emerald-500 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 tracking-normal">Total Mahasiswa</p>
                    <p class="text-2xl font-semibold text-gray-800 leading-none">{{ $students->count() }} <span class="text-sm text-gray-400">Orang</span></p>
                </div>
            </div>
        </div>

        @if($course->semester->is_active && in_array(strtoupper(auth()->user()->role), ['LABORAN', 'ASLAB'], true))
            <div class="mb-8 rounded-[2rem] border border-emerald-100 bg-emerald-50/70 p-6 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold tracking-normal text-emerald-500">Kelola Roster Kelas</p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-900">Tambahkan Mahasiswa</h3>
                        <p class="mt-1 text-sm text-slate-500">Cari mahasiswa berdasarkan nama atau NIM. Saran muncul setelah 3 karakter.</p>
                    </div>

                    <form
                        action="{{ route('courses.add-student', $course) }}"
                        method="POST"
                        class="flex w-full flex-col gap-3 sm:flex-row sm:items-start lg:max-w-2xl"
                        x-data="studentSearch(@js(route('courses.search-students', $course)))"
                        @submit="if (!selectedId || loading) $event.preventDefault()"
                        @click.outside="open = false"
                    >
                        @csrf
                        <div class="relative flex-1">
                            <label for="student_search" class="sr-only">Cari nama atau NIM mahasiswa</label>
                            <input type="hidden" name="student_id" :value="selectedId">
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"></path>
                                </svg>
                                <input
                                    id="student_search"
                                    type="search"
                                    x-model="query"
                                    @input="changed()"
                                    @focus="if (!selectedId && query.trim().length >= 3) { open = true; searchStudents(); }"
                                    @keydown.escape="open = false"
                                    @keydown.arrow-down.prevent="move(1)"
                                    @keydown.arrow-up.prevent="move(-1)"
                                    @keydown.enter.prevent="selectActive()"
                                    :aria-activedescendant="activeIndex >= 0 ? 'student-option-' + activeIndex : null"
                                    autocomplete="off"
                                    placeholder="Ketik nama atau NIM mahasiswa..."
                                    role="combobox"
                                    aria-controls="student_suggestions"
                                    :aria-expanded="open"
                                    class="w-full rounded-xl border-emerald-200 bg-white py-3 pl-11 pr-11 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                >
                                <svg x-show="loading" x-cloak class="absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                                </svg>
                            </div>

                            <div
                                id="student_suggestions"
                                x-show="open"
                                x-cloak
                                class="absolute z-30 mt-2 max-h-72 w-full overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl"
                                role="listbox"
                            >
                                <p x-show="loading" class="px-4 py-3 text-sm font-semibold text-slate-500">Mencari mahasiswa...</p>
                                <p x-show="!loading && failed" class="px-4 py-3 text-sm font-semibold text-red-600">Pencarian gagal. Silakan coba lagi.</p>
                                <p x-show="!loading && !failed && suggestions.length === 0" class="px-4 py-3 text-sm font-semibold text-slate-500">Tidak ada mahasiswa yang cocok atau mahasiswa sudah berada di kelas ini.</p>

                                <template x-for="(student, index) in suggestions" :key="student.id">
                                    <button
                                        type="button"
                                        @click="choose(student)"
                                        class="flex w-full items-center justify-between gap-4 rounded-xl px-4 py-3 text-left transition hover:bg-emerald-50 focus:bg-emerald-50 focus:outline-none"
                                        role="option" :id="'student-option-' + index" :aria-selected="index === activeIndex" :class="index === activeIndex ? 'bg-emerald-50' : ''"
                                    >
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-slate-800" x-text="student.name"></span>
                                            <span class="block truncate text-xs text-slate-400" x-text="student.email"></span>
                                        </span>
                                        <span class="shrink-0 text-xs font-bold text-emerald-700" x-text="student.id"></span>
                                    </button>
                                </template>
                            </div>

                            <p x-show="query.length > 0 && query.trim().length < 3" class="mt-2 text-xs font-semibold text-slate-500">Masukkan minimal 3 huruf atau angka.</p>
                            <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
                        </div>
                        <button
                            type="submit"
                            :disabled="!selectedId"
                            class="whitespace-nowrap rounded-xl bg-emerald-700 px-6 py-3 text-xs font-semibold tracking-normal text-white transition hover:bg-emerald-800 active:scale-95 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:active:scale-100"
                        >
                            + Tambahkan ke Kelas
                        </button>
                    </form>
                </div>
            </div>
        @endif

        {{-- TABEL MAHASISWA --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-[720px] w-full text-left border-collapse">
                    <thead class="bg-gray-50/50 text-xs font-semibold text-gray-400 border-b border-gray-100">
                        <tr>
                            <th class="px-8 py-6 tracking-normal whitespace-nowrap">No</th>
                            <th class="px-8 py-6 tracking-normal whitespace-nowrap">Mahasiswa</th>
                            <th class="px-8 py-6 tracking-normal whitespace-nowrap">NIM</th>
                            
                            @if(strtoupper(auth()->user()->role) === 'LABORAN')
                                <th class="px-8 py-6 tracking-normal text-center whitespace-nowrap">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($students as $index => $student)
                            <tr class="hover:bg-gray-50/50 transition-all group">
                                <td class="px-8 py-5 whitespace-nowrap text-gray-500 font-bold text-sm">
                                    {{ $index + 1 }}
                                </td>
                                <td class="px-8 py-5 whitespace-nowrap">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center overflow-hidden border border-gray-100">
                                            @if($student->avatar)
                                                <img src="{{ asset('storage/' . $student->avatar) }}" class="w-full h-full object-cover">
                                            @else
                                                <span class="text-slate-400 font-semibold text-xs">{{ strtoupper(substr($student->name, 0, 2)) }}</span>
                                            @endif
                                        </div>
                                        <span class="text-gray-800 font-semibold text-sm tracking-tight">{{ $student->name }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5 whitespace-nowrap">
                                    <span class="font-bold text-emerald-700">{{ $student->id }}</span>
                                </td>
                                
                                @if(strtoupper(auth()->user()->role) === 'LABORAN')
                                <td class="px-8 py-5 text-center whitespace-nowrap">
                                    <form action="{{ route('courses.remove-student', [$course, $student]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                onclick="return confirm('Apakah Anda yakin ingin MENGELUARKAN {{ $student->name }} dari kelas ini? Semua data terkait mahasiswa ini di kelas ini mungkin terpengaruh.')" 
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded-xl text-xs font-semibold tracking-normal transition-all shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path></svg>
                                            Keluarkan
                                        </button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ strtoupper(auth()->user()->role) === 'LABORAN' ? 4 : 3 }}" class="p-16 text-center">
                                    <div class="inline-flex p-6 bg-slate-50 rounded-[2rem] text-slate-300 mb-4">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    </div>
                                    <p class="text-xs font-semibold text-gray-400 tracking-normal">Belum ada mahasiswa yang bergabung di kelas ini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
