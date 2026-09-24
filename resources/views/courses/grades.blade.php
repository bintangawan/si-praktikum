<x-app-layout>
    <x-slot name="header_title">Penilaian · {{ $course->course_name }}</x-slot>

    <div class="mx-auto max-w-[95rem] space-y-6 px-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('dashboard') }}" class="mb-3 inline-flex text-sm font-semibold text-emerald-700">← Kembali ke panel dosen</a>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Penilaian mahasiswa</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $course->course_name }} · {{ $course->class_group }} · {{ $course->semester?->name }}</p>
            </div>
            <a href="{{ route('courses.show', $course) }}" class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-emerald-300">Buka kelas</a>
        </div>

        @if($course->isArchived())
            <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Kelas arsip hanya dapat dibaca; nilai tidak dapat diubah.</p>
        @endif
        @if($errors->any())
            <div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif

        <section class="rounded-2xl border border-sky-100 bg-sky-50/70 p-5 text-sm leading-6 text-sky-950">
            <p class="font-semibold">Perhitungan nilai</p>
            <p class="mt-1">Nilai laporan praktikum adalah rata-rata nilai Aslab dan Laboran di setiap modul, lalu dirata-ratakan untuk seluruh modul. Nilai akhir adalah rata-rata nilai Laporan Praktikum, UTS, dan UAS.</p>
            <p class="mt-1 text-xs text-sky-800">Predikat: A ≥ 86 · B ≥ 75 · C ≥ 60 · D ≥ 40 · E di bawah 40 (nilai maksimal 100).</p>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Mahasiswa</th>
                            <th class="px-4 py-4 text-center">Laporan Praktikum</th>
                            <th class="px-4 py-4 text-center">UTS</th>
                            <th class="px-4 py-4 text-center">UAS</th>
                            <th class="px-4 py-4 text-center">Nilai Akhir</th>
                            <th class="px-5 py-4">Simpan nilai dosen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            @php
                                $readyModules = $row->modules->filter(fn ($module) => $module->submission?->is_completed && $module->submission->aslab_score !== null && $module->submission->laboran_score !== null)->count();
                            @endphp
                            <tr class="align-top hover:bg-slate-50/50">
                                <td class="px-5 py-5">
                                    <p class="font-semibold text-slate-900">{{ $row->student->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $row->student->id }}</p>
                                    <details class="mt-3">
                                        <summary class="cursor-pointer text-xs font-semibold text-emerald-700">Rincian nilai modul ({{ $readyModules }}/{{ $meetings->count() }})</summary>
                                        <div class="mt-3 min-w-[330px] space-y-2">
                                            @foreach($row->modules as $module)
                                                <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2 text-xs">
                                                    <span class="min-w-0 truncate text-slate-700">Modul {{ $module->meeting->meeting_number }} · {{ $module->meeting->title }}</span>
                                                    @if($module->submission)
                                                        <span class="shrink-0 text-right text-slate-600">Aslab {{ $module->submission->aslab_score ?? '—' }} · Lab {{ $module->submission->laboran_score ?? '—' }}<br><strong class="text-emerald-700">Rata-rata {{ $module->score === null ? '—' : number_format($module->score, 2, ',', '.') }}</strong></span>
                                                    @else
                                                        <span class="shrink-0 text-amber-700">Belum mengumpulkan</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                </td>
                                <td class="px-4 py-5 text-center">
                                    @if($row->laprak !== null)
                                        <span class="font-bold text-slate-900">{{ number_format($row->laprak, 2, ',', '.') }}</span><span class="ml-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">{{ $row->laprak_letter }}</span>
                                    @else
                                        <span class="text-xs text-amber-700">Belum lengkap<br>{{ $readyModules }}/{{ $meetings->count() }} modul dinilai</span>
                                    @endif
                                </td>
                                <td class="px-4 py-5 text-center">
                                    @if($row->uts !== null)<span class="font-bold">{{ number_format($row->uts, 2, ',', '.') }}</span><span class="ml-1 text-xs font-semibold text-slate-500">{{ $row->uts_letter }}</span>@else<span class="text-slate-400">—</span>@endif
                                </td>
                                <td class="px-4 py-5 text-center">
                                    @if($row->uas !== null)<span class="font-bold">{{ number_format($row->uas, 2, ',', '.') }}</span><span class="ml-1 text-xs font-semibold text-slate-500">{{ $row->uas_letter }}</span>@else<span class="text-slate-400">—</span>@endif
                                </td>
                                <td class="px-4 py-5 text-center">
                                    @if($row->final !== null)<span class="text-lg font-bold text-emerald-800">{{ number_format($row->final, 2, ',', '.') }}</span><span class="ml-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">{{ $row->final_letter }}</span>@else<span class="text-xs text-slate-400">Menunggu nilai lengkap</span>@endif
                                </td>
                                <td class="px-5 py-5">
                                    @if($row->ready && !$course->isArchived())
                                        <form method="POST" action="{{ route('courses.grades.update', [$course, $row->student]) }}" class="flex flex-wrap items-end gap-2">
                                            @csrf @method('PUT')
                                            <label class="text-xs font-semibold text-slate-500">UTS<input name="uts_score" type="number" required min="0" max="100" step="0.01" value="{{ $row->uts }}" class="mt-1 block w-24 rounded-lg border-slate-200 text-sm text-slate-900"></label>
                                            <label class="text-xs font-semibold text-slate-500">UAS<input name="uas_score" type="number" required min="0" max="100" step="0.01" value="{{ $row->uas }}" class="mt-1 block w-24 rounded-lg border-slate-200 text-sm text-slate-900"></label>
                                            <button class="rounded-lg bg-emerald-700 px-3 py-2.5 text-xs font-bold text-white hover:bg-emerald-800">Simpan</button>
                                        </form>
                                    @else
                                        <p class="max-w-[230px] text-xs leading-5 text-slate-500">Nilai UTS dan UAS terbuka setelah seluruh modul mahasiswa ini mendapat ACC dan nilai Aslab serta Laboran lengkap.</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-14 text-center text-sm text-slate-500">Belum ada mahasiswa terdaftar di kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
