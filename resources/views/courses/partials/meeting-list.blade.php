<section>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Modul praktikum</h2>
            <p class="mt-1 text-sm text-slate-500">Setiap kartu memiliki materi dan tempat upload laprak tersendiri.</p>
        </div>
        <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-800">{{ $course->meetings->count() }} modul</span>
    </div>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        @forelse($course->meetings->sortBy('meeting_number') as $meeting)
            @php
                $student = auth()->user()->hasRole('Mahasiswa');
                $submission = $student ? $meeting->submissions->firstWhere('student_id', auth()->id()) : null;
                $attendance = $student ? $meeting->attendances->first() : null;
                $status = $submission?->studentStatus();
                $canResubmit = $submission?->canResubmit() ?? false;
                $pastDeadline = $meeting->deadline && now()->gt($meeting->deadline);
                $statusClass = match($status) {
                    'Diterima' => 'bg-emerald-50 text-emerald-800 border-emerald-100',
                    'Revisi' => 'bg-amber-50 text-amber-800 border-amber-100',
                    'Ditolak' => 'bg-red-50 text-red-700 border-red-100',
                    default => 'bg-amber-50 text-amber-700 border-amber-100',
                };
            @endphp

            <article class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-module="{{ $meeting->meeting_number }}">
                <div class="mb-3 flex items-start justify-between gap-3">
                    <span class="text-sm font-semibold text-emerald-700">Modul {{ $meeting->meeting_number }}</span>
                    @if($student)
                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $submission ? $statusClass : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                            {{ $submission ? $status : 'Belum mengumpulkan' }}
                        </span>
                    @endif
                </div>

                <h3 class="text-lg font-semibold text-slate-900">{{ $meeting->title }}</h3>
                <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">{{ $meeting->description ?: 'Buka modul untuk melihat materi dan mengirim laprak.' }}</p>
                <p class="mt-4 text-xs text-slate-500">Batas waktu: {{ $meeting->deadline ? $meeting->deadline->format('d M Y H:i').' WIB' : 'Tidak dibatasi' }}</p>

                @if($student)
                    <p class="mt-3 text-xs text-slate-600">Presensi: {{ $attendance ? \App\Models\Attendance::label($attendance->status) : ($meeting->attendances_count ? 'Tanpa Keterangan' : 'Belum Presensi') }}</p>
                    <div class="mt-4 rounded-xl border p-3 text-sm {{ $submission ? $statusClass : ($pastDeadline ? 'border-red-100 bg-red-50 text-red-700' : 'border-slate-200 bg-slate-50 text-slate-600') }}">
                        @if($submission)
                            <p class="font-semibold">{{ $status }}</p>
                            <p class="mt-1 text-xs">Aslab: {{ $submission->aslab_status }} · Laboran: {{ $submission->laboran_status }}</p>
                            @if($canResubmit)
                                <p class="mt-2 text-xs">Catatan: {{ $submission->histories->first(fn ($history) => $history->feedback)?->feedback ?: 'Buka tugas untuk melihat detail pemeriksaan.' }}</p>
                            @endif
                        @else
                            <p>{{ $pastDeadline ? 'Belum mengumpulkan — waktu habis' : 'Laprak belum diunggah.' }}</p>
                        @endif
                    </div>
                @endif

                <div class="mt-auto flex flex-wrap gap-3 pt-5">
                    @if($student)
                        <a href="{{ route('mahasiswa.submissions.manage', $meeting) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">
                            {{ !$course->semester->is_active || $submission?->is_completed ? 'Lihat laporan' : ($canResubmit ? 'Upload perbaikan' : ($submission ? 'Lihat status' : 'Upload laprak')) }}
                        </a>
                    @else
                        <a href="{{ route('submissions.index', $meeting) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white">Laporan ({{ $meeting->submissions_count }})</a>
                        <a href="{{ route('attendance.index', $meeting) }}" class="rounded-xl bg-slate-50 px-4 py-2.5 text-sm text-slate-700">Presensi</a>
                    @endif
                    @if($meeting->module_drive_link)
                        <a href="{{ \App\Services\DriveLink::preview($meeting->module_drive_link) ?? $meeting->module_drive_link }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm text-emerald-800">Lihat materi</a>
                    @endif
                </div>
            </article>
        @empty
            <p class="col-span-full rounded-2xl border border-dashed border-slate-200 p-10 text-center text-sm text-slate-500">Belum ada modul. Pengelola kelas perlu menambahkan modul sebelum mahasiswa dapat mengumpulkan laprak.</p>
        @endforelse
    </div>
</section>
