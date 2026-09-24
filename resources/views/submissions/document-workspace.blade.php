@php
    $course = $task->course;
    $isFinal = $task instanceof \App\Models\FinalTask;
    $isStudent = auth()->user()->hasRole('Mahasiswa');
    $canResubmit = $submission?->canResubmit() ?? false;
    $locked = $course->isArchived() || ($submission?->is_completed ?? false) || ($task->deadline && now()->gt($task->deadline) && !$canResubmit);
    $canReview = !$isStudent && !$course->isArchived() && $submission && !$submission->is_completed
        && ((auth()->user()->hasRole('Aslab') && $submission->aslab_status !== 'ACC')
            || (auth()->user()->hasRole('Laboran') && $submission->aslab_status === 'ACC' && $submission->laboran_status !== 'ACC')
            || ($isFinal && auth()->user()->hasRole('Dosen') && $submission->aslab_status === 'ACC' && $submission->laboran_status === 'ACC' && $submission->dosen_status !== 'ACC'));
    $canScore = !$isStudent && !$isFinal && !$course->isArchived() && $submission?->is_completed
        && (auth()->user()->hasRole('Aslab') || auth()->user()->hasRole('Laboran'));
    $studentStatus = $submission?->studentStatus();
    $statusClass = match($studentStatus) {
        'Diterima' => 'bg-emerald-50 text-emerald-800',
        'Revisi' => 'bg-amber-50 text-amber-800',
        'Ditolak' => 'bg-red-50 text-red-700',
        default => 'bg-amber-50 text-amber-700',
    };
@endphp

<x-app-layout>
    <x-slot name="header_title">{{ $isFinal ? 'Laporan Final' : 'Modul '.$task->meeting_number.': '.$task->title }}</x-slot>
    <a class="mb-5 inline-block text-sm font-semibold text-emerald-700" href="{{ route('courses.show', $course) }}">Kembali ke kelas {{ $course->course_name }}</a>
    @if($errors->any())<div role="alert" class="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <p class="mb-5 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $task->description }}</p>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(300px,1fr)]">
        <x-document-preview :url="$submission?->previewUrl()" />
        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-semibold">{{ $isStudent ? 'Pengumpulan laprak' : $submission->student->name }}</h2>
                <p class="mb-4 text-sm text-slate-500">Batas waktu: {{ $task->deadline ? $task->deadline->format('d M Y H:i').' WIB' : 'Tidak dibatasi' }}</p>

                @if($submission)
                    <div class="mb-5 space-y-3 text-sm">
                        <span class="inline-flex rounded-full px-3 py-1.5 font-semibold {{ $statusClass }}">{{ $studentStatus }}</span>
                        <p>Versi dokumen: <strong>{{ $submission->document_version }}</strong></p>
                        <p>Aslab: {{ $submission->aslab_status }} · Laboran: {{ $submission->laboran_status }}</p>
                        @if($isFinal)<p>Dosen: {{ $submission->dosen_status }}</p>@endif
                    </div>
                @endif

                @if($isStudent)
                    @if($locked)
                    <p class="rounded-xl bg-slate-50 p-4 text-sm">{{ $course->isArchived() ? 'Kelas arsip hanya dapat dibaca.' : ($submission?->is_completed ? 'Laporan diterima dan proses pemeriksaan selesai.' : 'Batas pengumpulan telah ditutup.') }}</p>
                    @else
                        <form method="POST" action="{{ $isFinal ? ($submission ? route('final-tasks.update', $submission) : route('final-tasks.submit', $task)) : ($submission ? route('submissions.update', $submission) : route('submissions.store', $task)) }}" class="space-y-4" x-data="driveSubmission(@js(old('submission_link', $submission?->submission_link ?? '')))">
                            @csrf
                            @if($submission) @method('PUT') <input type="hidden" name="document_version" value="{{ $submission->document_version }}"> @endif
                            <label class="block text-sm font-semibold">Link PDF Google Drive<input type="url" name="submission_link" x-model="link" @input.debounce.300ms="preview()" required maxlength="2048" class="mt-2 w-full rounded-xl border-slate-200 text-sm" placeholder="https://drive.google.com/file/d/.../view"></label>
                            <p class="text-xs leading-6 text-slate-500">Unggah PDF ke Google Drive, atur akses baca, lalu tempel link file. Gunakan file baru untuk setiap perbaikan agar riwayat tetap jelas.</p>
                            <label class="block text-sm font-semibold">Catatan<textarea name="notes" maxlength="5000" rows="3" class="mt-2 w-full rounded-xl border-slate-200 text-sm">{{ old('notes', $submission?->notes) }}</textarea></label>
                            <button class="w-full rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800">{{ $submission ? 'Kirim versi perbaikan' : 'Upload laprak' }}</button>
                        </form>
                    @endif
                @elseif($canReview)
                    <form method="POST" action="{{ $isFinal ? route('final-tasks.approve', $submission) : route('submissions.approve', $submission) }}" class="space-y-4" x-data="{ scoreEnabled: false }">
                        @csrf @if($isFinal) @method('PATCH') @endif
                        <input type="hidden" name="document_version" value="{{ $submission->document_version }}">
                        @unless($isFinal)
                            <label class="block text-sm font-semibold">Nilai Modul (0–100)
                                <input x-ref="score" name="score" type="number" min="0" max="100" step="0.01" disabled :disabled="!scoreEnabled" :required="scoreEnabled" value="{{ old('score') }}" class="mt-2 block w-36 rounded-xl border-slate-200 text-sm disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                            </label>
                            <p class="text-xs text-slate-500">Pilih ACC untuk mengaktifkan dan mengisi nilai. Nilai wajib diisi untuk menyimpan ACC.</p>
                        @endunless
                        <label class="block text-sm font-semibold">Feedback (wajib untuk revisi atau penolakan)<textarea name="{{ $isFinal ? 'notes' : 'feedback' }}" maxlength="5000" rows="4" class="mt-2 w-full rounded-xl border-slate-200">{{ old($isFinal ? 'notes' : 'feedback') }}</textarea></label>
                        <div class="flex flex-wrap gap-3">
                            <button name="status" value="ACC" @click="scoreEnabled = {{ $isFinal ? 'false' : 'true' }}; if (scoreEnabled) $nextTick(() => $refs.score.focus())" class="rounded-xl bg-emerald-700 px-4 py-3 text-sm font-semibold text-white">Terima / ACC</button>
                            <button name="status" value="REVISI" @click="scoreEnabled = false" class="rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">Minta revisi</button>
                            <button name="status" value="DITOLAK" @click="scoreEnabled = false" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">Tolak laporan</button>
                        </div>
                    </form>
                @else
                    <p class="text-sm text-slate-500">Mode akses baca. Laporan selesai, semester diarsipkan, atau masih menunggu tahap pemeriksa sebelumnya.</p>
                @endif

                @if($canScore)
                    @php
                        $scoreField = auth()->user()->hasRole('Aslab') ? 'aslab_score' : 'laboran_score';
                    @endphp
                    <section class="mt-6 border-t border-slate-100 pt-5">
                        <h3 class="font-semibold text-slate-900">Nilai {{ auth()->user()->hasRole('Aslab') ? 'Aslab' : 'Laboran' }} modul {{ $submission->meeting->meeting_number }}</h3>
                        @if($submission->{$scoreField} === null)
                            <p class="mt-1 text-xs leading-5 text-amber-700">Nilai ACC lama belum tercatat. Isi nilai 0-100 untuk melengkapi rekap.</p>
                            <form method="POST" action="{{ route('submissions.score', $submission) }}" class="mt-3 flex flex-wrap items-end gap-3">
                                @csrf @method('PUT')
                                <label class="text-sm font-semibold">Nilai
                                    <input name="score" type="number" min="0" max="100" step="0.01" required value="{{ old('score') }}" class="mt-1 block w-32 rounded-xl border-slate-200 text-sm">
                                </label>
                                <button class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Simpan nilai</button>
                            </form>
                        @else
                            <p class="mt-2 text-sm text-slate-600">Nilai sudah disimpan: <strong class="text-emerald-800">{{ $submission->{$scoreField} }}</strong></p>
                        @endif
                        <p class="mt-3 text-xs text-slate-500">Nilai Aslab: <strong>{{ $submission->aslab_score ?? '-' }}</strong> | Nilai Laboran: <strong>{{ $submission->laboran_score ?? '-' }}</strong></p>
                    </section>
                @endif
            </section>

            @if($submission)
                <section class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 class="mb-4 font-semibold">Riwayat dokumen dan pemeriksaan</h2>
                    <div class="space-y-4">
                        @foreach($submission->histories as $history)
                            <article class="rounded-xl bg-slate-50 p-4 text-sm">
                                <p class="font-semibold">Dokumen v{{ $history->document_version }} · {{ $history->action_type }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $history->reviewer?->name ?? 'Mahasiswa' }} · {{ $history->created_at->format('d M Y H:i') }}</p>
                                <p class="mt-2 whitespace-pre-line">{{ $history->feedback }}</p>
                                @if($history->previewUrl())
                                    <button type="button" class="mt-3 font-semibold text-emerald-700" @click="$dispatch('document-compare', {url: @js($history->previewUrl()), trigger: $el})">Bandingkan versi ini</button>
                                    <a class="ml-3 text-emerald-700" href="{{ $history->previewUrl() }}" target="_blank" rel="noopener noreferrer">Buka pembanding</a>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
