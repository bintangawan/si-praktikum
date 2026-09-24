@php
    $activeCourseCount = $dosenCourses->reject(fn ($course) => $course->isArchived())->count();
    $totalModuleCount = $dosenCourses->sum('meetings_count');
@endphp

<div class="space-y-6 sm:space-y-8">
    <section class="relative overflow-hidden rounded-3xl bg-emerald-950 p-6 text-white shadow-xl shadow-emerald-950/10 sm:p-8 lg:p-10">
        <div class="pointer-events-none absolute -right-10 -top-16 h-56 w-56 rounded-full bg-emerald-400/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 left-1/3 h-48 w-48 rounded-full bg-emerald-300/10 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-emerald-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v12m6-6H6m14 9H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2Z"/></svg>
                    Panel Dosen
                </div>
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Halo, Dosen!</h2>
                <p class="mt-1 break-words text-sm font-medium text-emerald-100 sm:text-base">{{ $user->name }}</p>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-emerald-100/80 sm:text-base sm:leading-7">
                    Kelola seluruh kelas praktikum yang Anda ampu dari satu panel.
                </p>
            </div>
            <div class="hidden h-20 w-20 shrink-0 items-center justify-center rounded-3xl border border-white/10 bg-white/10 text-emerald-100 sm:flex">
                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4" aria-label="Ringkasan kelas dosen">
        <article class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Zm0 13A2.5 2.5 0 0 1 6.5 16H20M8 7h8m-8 4h8"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500">Total kelas</p>
                <p class="mt-0.5 text-2xl font-bold tracking-tight text-slate-900">{{ $dosenCourses->count() }}</p>
            </div>
        </article>

        <article class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500">Kelas aktif</p>
                <p class="mt-0.5 text-2xl font-bold tracking-tight text-slate-900">{{ $activeCourseCount }}</p>
            </div>
        </article>

        <article class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500">Total modul</p>
                <p class="mt-0.5 text-2xl font-bold tracking-tight text-slate-900">{{ $totalModuleCount }}</p>
            </div>
        </article>
    </section>

    <section>
        <div class="mb-4 flex flex-col gap-1 sm:mb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Kelas yang diampu</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">Semua kelas yang ditugaskan kepada Anda, termasuk kelas dari semester sebelumnya.</p>
            </div>
        </div>

        @if($dosenCourses->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center sm:py-16">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5Zm0 13A2.5 2.5 0 0 1 6.5 16H20M8 7h8m-8 4h8"/></svg>
                </div>
                <h4 class="mt-4 text-base font-bold text-slate-800">Belum ada kelas yang ditugaskan.</h4>
                <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">Hubungi Laboran agar akun Anda ditambahkan sebagai dosen pada kelas praktikum.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
                @foreach($dosenCourses as $course)
                    @php($isActive = !$course->isArchived())
                    <article class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg">
                        <div class="flex flex-1 flex-col p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-700 group-hover:text-white">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $isActive ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $isActive ? 'Aktif' : 'Diarsipkan' }}
                                </span>
                            </div>

                            <div class="mt-5 min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-500">{{ $course->semester?->name ?? 'Semester' }}</p>
                                <h4 class="mt-1 break-words text-lg font-bold leading-snug text-slate-900 sm:text-xl">{{ $course->course_name }}</h4>
                                <p class="mt-1 text-sm text-slate-500">{{ $course->class_group }}</p>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-xs font-medium text-slate-500">Mahasiswa terdaftar</p>
                                    <p class="mt-1 text-sm font-bold text-slate-800">{{ $course->students_count }}</p>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <p class="text-xs font-medium text-slate-500">Modul</p>
                                    <p class="mt-1 text-sm font-bold text-slate-800">{{ $course->meetings_count }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-500">
                                <span><span class="font-semibold text-slate-600">Aslab:</span> {{ $course->aslab?->name ?? '—' }}</span>
                                <span><span class="font-semibold text-slate-600">Laboran:</span> {{ $course->laboran?->name ?? '—' }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 border-t border-slate-100 bg-slate-50/70 p-4 sm:grid-cols-3">
                            <a href="{{ route('courses.show', $course) }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 sm:col-span-3">
                                Buka kelas
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7-7 7 7-7 7"/></svg>
                            </a>
                            @if($isActive)
                                <a href="{{ route('courses.grades.index', $course) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-center text-xs font-semibold text-emerald-800 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">Penilaian</a>
                                <a href="{{ route('courses.students', $course) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-xs font-semibold text-slate-600 transition hover:border-emerald-200 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">Daftar mahasiswa</a>
                                <a href="{{ route('attendance.report', $course) }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-center text-xs font-semibold text-slate-600 transition hover:border-emerald-200 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">Rekap presensi</a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
