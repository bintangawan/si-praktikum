<x-app-layout>
    <x-slot name="header_title">Submission: {{ $meeting->course->course_name }}</x-slot>

    @php
        $course = $meeting->course;
        $students = $course->students->sortBy('name');
        $publishedMeetings = $courseMeetings->whereNotNull('published_at');
    @endphp

    <div class="mx-auto max-w-[95rem] space-y-6 px-4" x-data="{
        detailsOpen: false, activeStudentId: null, previewOpen: false,
        previewUrl: null, driveUrl: null, previewTitle: '',
        closeDetails() { this.detailsOpen = false; this.activeStudentId = null; },
        closePreview() { this.previewOpen = false; this.previewUrl = null; }
    }" @keydown.escape.window="previewOpen ? closePreview() : closeDetails()">
        @if($course->isArchived())
            <p class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Kelas arsip hanya dapat dibaca.</p>
        @endif

        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('courses.show', $course) }}" class="mb-3 inline-flex text-sm font-semibold text-emerald-700 hover:text-emerald-800">← Kembali ke kelas</a>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $course->course_name }} · {{ $course->class_group }}</h2>
                <p class="mt-1 text-sm text-slate-500">Daftar mahasiswa dan pengumpulan seluruh modul · {{ $courseMeetings->count() }} modul</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('manageModules', $course)
                    @if(!$course->isArchived())
                        <a href="{{ route('courses.modules.edit', $course) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-emerald-300">Kelola modul</a>
                    @endif
                @endcan
                @if(!$course->isArchived())
                    <form action="{{ route('meetings.update-deadline', $meeting) }}" method="POST" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-2">
                        @csrf @method('PUT')
                        <label class="sr-only" for="deadline">Deadline {{ $meeting->title }}</label>
                        <input id="deadline" type="datetime-local" name="deadline" required value="{{ $meeting->deadline?->format('Y-m-d\TH:i') }}" class="rounded-lg border-slate-200 text-xs">
                        <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">Simpan deadline</button>
                    </form>
                @endif
            </div>
        </div>

        @if($meeting->description)
            <section class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $meeting->title }} · Instruksi</p>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $meeting->description }}</p>
            </section>
        @endif

        <section class="grid gap-3 sm:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs font-semibold text-slate-500">Mahasiswa</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $students->count() }}</p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs font-semibold text-slate-500">Modul dibuka</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ $publishedMeetings->count() }}<span class="text-base font-medium text-slate-400"> / {{ $courseMeetings->count() }}</span></p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs font-semibold text-slate-500">Laporan masuk</p><p class="mt-1 text-2xl font-bold text-emerald-700">{{ $publishedMeetings->sum(fn ($module) => $module->submissions->count()) }}</p></article>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/70 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <label class="relative block w-full sm:max-w-md">
                    <span class="sr-only">Cari nama atau NIM</span>
                    <input id="studentSearch" type="search" placeholder="Cari Nama atau NIM..." class="w-full rounded-xl border-slate-200 pl-4 pr-4 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                </label>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-600">
                    <input id="incompleteOnly" type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    Hanya tampilkan yang belum kumpul semua modul
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1200px] text-left text-sm">
                    <thead class="border-b border-slate-100 bg-white text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Mahasiswa</th>
                            <th class="px-5 py-4 text-center">Laporan masuk</th>
                            @foreach($courseMeetings as $module)
                                <th class="min-w-36 px-3 py-4 text-center">Status Modul {{ $module->meeting_number }}</th>
                            @endforeach
                            <th class="px-6 py-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTable" class="divide-y divide-slate-100">
                        @foreach($students as $student)
                            @php
                                $submittedCount = $publishedMeetings->filter(fn ($module) => $module->submissions->contains('student_id', (string) $student->id))->count();
                            @endphp
                            <tr class="student-row hover:bg-slate-50/70" data-name="{{ strtolower($student->name) }}" data-nim="{{ strtolower($student->id) }}" data-complete="{{ $submittedCount === $publishedMeetings->count() && $publishedMeetings->isNotEmpty() ? 'true' : 'false' }}">
                                <td class="px-6 py-5">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-900">{{ $student->name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $student->id }}</p>
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-center align-middle">
                                    <div class="mx-auto flex min-h-[4.5rem] w-20 flex-col items-center justify-center rounded-lg px-2 py-2 {{ $submittedCount === $publishedMeetings->count() && $publishedMeetings->isNotEmpty() ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">
                                        <span class="text-base font-bold leading-5">{{ $submittedCount }}/{{ $publishedMeetings->count() }}</span>
                                        <span class="mt-1 text-xs font-medium leading-4">modul</span>
                                    </div>
                                </td>
                                @foreach($courseMeetings as $module)
                                    @php
                                        $moduleSubmission = $module->submissions->firstWhere('student_id', (string) $student->id);
                                    @endphp
                                    <td class="px-3 py-5 text-center">
                                        @if(!$module->isPublished())
                                            <span class="text-xs text-slate-400">Belum dibuka</span>
                                        @elseif($moduleSubmission)
                                            <div class="flex flex-col items-center gap-1.5 text-xs">
                                                @foreach(['Aslab' => $moduleSubmission->aslab_status, 'Laboran' => $moduleSubmission->laboran_status] as $reviewer => $status)
                                                    @php
                                                        $statusColor = match (strtoupper($status)) {
                                                            'ACC' => 'border-emerald-400 bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200',
                                                            'REVISI' => 'border-amber-300 bg-amber-50 text-amber-800',
                                                            'DITOLAK' => 'border-red-300 bg-red-50 text-red-800',
                                                            default => 'border-slate-200 bg-white text-slate-600',
                                                        };
                                                    @endphp
                                                    <span class="rounded-full border px-2.5 py-1 {{ $statusColor }}">{{ $reviewer }}: {{ $status }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-500">Belum kumpul</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-6 py-5 text-center"><button type="button" @click="activeStudentId = @js((string) $student->id); detailsOpen = true" class="rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-800">Detail</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p id="studentEmpty" class="hidden px-6 py-12 text-center text-sm text-slate-500">Pencarian tidak menemukan mahasiswa.</p>
            </div>
        </section>

        @foreach($students as $student)
            <div x-cloak x-show="detailsOpen && activeStudentId === @js((string) $student->id)" class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6" role="dialog" aria-modal="true" @click.self="closeDetails()">
                <section class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <header class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                        <div><p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Detail pengumpulan</p><h2 class="mt-1 text-lg font-bold text-slate-900">{{ $student->name }}</h2><p class="text-sm text-slate-500">NIM {{ $student->id }} · {{ $courseMeetings->count() }} modul</p></div>
                        <button type="button" @click="closeDetails()" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600">Tutup</button>
                    </header>
                    <div class="min-h-0 space-y-3 overflow-y-auto p-4 sm:p-6">
                        @foreach($courseMeetings as $module)
                            @php
                                $sub = $module->submissions->firstWhere('student_id', (string) $student->id);
                                $aslabScore = $sub && $sub->aslab_score !== null ? (float) $sub->aslab_score : null;
                                $laboranScore = $sub && $sub->laboran_score !== null ? (float) $sub->laboran_score : null;
                                $moduleReportScore = $aslabScore !== null && $laboranScore !== null ? round(($aslabScore * 0.8) + ($laboranScore * 0.2), 2) : null;
                                $moduleGradeLetter = \App\Support\GradeScale::letter($moduleReportScore);
                                $aslabStatusColor = !$sub ? '' : match(strtoupper($sub->aslab_status)) {'ACC' => 'border-emerald-400 bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200', 'REVISI' => 'border-amber-300 bg-amber-50 text-amber-800', 'DITOLAK' => 'border-red-300 bg-red-50 text-red-800', default => 'border-slate-200 bg-white text-slate-600'};
                                $laboranStatusColor = !$sub ? '' : match(strtoupper($sub->laboran_status)) {'ACC' => 'border-emerald-400 bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200', 'REVISI' => 'border-amber-300 bg-amber-50 text-amber-800', 'DITOLAK' => 'border-red-300 bg-red-50 text-red-800', default => 'border-slate-200 bg-white text-slate-600'};
                                $reviewerHasApproved = $sub && ((auth()->user()->hasRole('Aslab') && $sub->aslab_status === 'ACC') || (auth()->user()->hasRole('Laboran') && $sub->laboran_status === 'ACC'));
                                $laboranWaitingAslab = $sub && auth()->user()->hasRole('Laboran') && $sub->aslab_status !== 'ACC';
                            @endphp
                            <article class="rounded-xl border border-slate-200 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-slate-900">Modul {{ $module->meeting_number }}</h3>
                                        @if(!$module->isPublished())
                                            <p class="mt-1 text-xs font-medium text-slate-400">Modul belum dibuka untuk pengumpulan.</p>
                                        @elseif(!$sub)
                                            <p class="mt-1 text-xs font-medium text-amber-700">Belum mengumpulkan · deadline {{ $module->deadline?->format('d M Y H:i') ?? 'tidak dibatasi' }}</p>
                                        @else
                                            <p class="mt-1 text-xs text-slate-500">Dikumpulkan {{ $sub->last_upload_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB · {{ $sub->histories_count }} riwayat</p>
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs">
                                                <span class="rounded-full border px-2.5 py-1 {{ $aslabStatusColor }}">Aslab: {{ $sub->aslab_status }}</span>
                                                <span class="rounded-full border px-2.5 py-1 {{ $laboranStatusColor }}">Laboran: {{ $sub->laboran_status }}</span>
                                            </div>
                                            <dl class="mt-3 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                                                <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-slate-500">Nilai Aslab</dt><dd class="mt-1 font-semibold text-slate-900">{{ $aslabScore !== null ? number_format($aslabScore, 2, ',', '.') : '—' }}</dd></div>
                                                <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-slate-500">Nilai Laboran</dt><dd class="mt-1 font-semibold text-slate-900">{{ $laboranScore !== null ? number_format($laboranScore, 2, ',', '.') : '—' }}</dd></div>
                                                <div class="rounded-lg bg-emerald-50 px-3 py-2"><dt class="text-emerald-700">Nilai Modul</dt><dd class="mt-1 font-semibold text-emerald-800">{{ $moduleReportScore !== null ? number_format($moduleReportScore, 2, ',', '.') : '—' }}</dd></div>
                                                <div class="rounded-lg bg-emerald-50 px-3 py-2"><dt class="text-emerald-700">Nilai Huruf</dt><dd class="mt-1 font-semibold text-emerald-800">{{ $moduleGradeLetter ?? '—' }}</dd></div>
                                            </dl>
                                        @endif
                                    </div>
                                    @if($sub)
                                        <div class="flex shrink-0 flex-wrap gap-2">
                                            <button type="button" @click="previewUrl = @js($sub->previewUrl()); driveUrl = @js($sub->documentUrl()); previewTitle = @js($student->name.' · Modul '.$module->meeting_number); previewOpen = true" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">Preview</button>
                                            <a href="{{ $sub->documentUrl() }}" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Buka Drive</a>
                                            @if($reviewerHasApproved)
                                                <button type="button" disabled class="cursor-not-allowed rounded-lg border border-emerald-400 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200">Sudah ACC</button>
                                            @elseif($course->isArchived())
                                                <button type="button" disabled class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-400">Arsip</button>
                                            @elseif($laboranWaitingAslab)
                                                <button type="button" disabled class="cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500">Menunggu Aslab</button>
                                            @else
                                                <a href="{{ route('submissions.handler', $sub) }}" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white">Review</a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>
        @endforeach

        <template x-teleport="body">
            <div x-cloak x-show="previewOpen" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/75 p-3 sm:p-6" role="dialog" aria-modal="true" @click.self="closePreview()">
                <section class="flex h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-6">
                        <div class="min-w-0"><h2 class="truncate font-semibold text-slate-900">Preview laprak · <span x-text="previewTitle"></span></h2><p class="mt-1 text-xs text-slate-500">Jika preview tidak tampil, pastikan dokumen dapat diakses melalui Google Drive.</p></div>
                        <div class="flex shrink-0 gap-2"><a :href="driveUrl" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Buka Drive</a><button type="button" @click="closePreview()" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">Tutup</button></div>
                    </header>
                    <template x-if="previewUrl"><iframe :src="previewUrl" title="Preview laporan praktikum" class="min-h-0 w-full flex-1 bg-slate-50" referrerpolicy="no-referrer"></iframe></template>
                    <p x-show="!previewUrl" class="flex flex-1 items-center justify-center p-8 text-center text-sm text-slate-500">Preview tidak tersedia. Gunakan tombol Buka Drive.</p>
                </section>
            </div>
        </template>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const search = document.getElementById('studentSearch');
            const incomplete = document.getElementById('incompleteOnly');
            const rows = [...document.querySelectorAll('.student-row')];
            const empty = document.getElementById('studentEmpty');
            const filter = () => {
                const query = search.value.trim().toLowerCase();
                let visible = 0;
                rows.forEach(row => {
                    const matches = (row.dataset.name.includes(query) || row.dataset.nim.includes(query))
                        && (!incomplete.checked || row.dataset.complete !== 'true');
                    row.classList.toggle('hidden', !matches);
                    if (matches) visible++;
                });
                empty.classList.toggle('hidden', visible !== 0);
            };
            search.addEventListener('input', filter);
            incomplete.addEventListener('change', filter);
        });
    </script>
</x-app-layout>
