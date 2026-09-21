<x-app-layout>
    <x-slot name="header_title">Kelola modul: {{ $course->course_name }}</x-slot>

    @php
        $savedRows = $course->meetings->map(fn ($meeting) => [
            'id' => $meeting->id,
            'meeting_number' => $meeting->meeting_number,
            'title' => $meeting->title,
            'description' => $meeting->description ?? '',
            'module_drive_link' => $meeting->module_drive_link ?? '',
            'deadline' => $meeting->deadline?->format('Y-m-d\TH:i') ?? '',
            'is_published' => $meeting->isPublished(),
        ])->all();
        $rows = collect(old('modules', $savedRows))->values()->map(function ($row, $index) use ($course) {
            $meeting = filled($row['id'] ?? null) ? $course->meetings->firstWhere('id', (int) $row['id']) : null;

            return [
                'id' => $row['id'] ?? null,
                'meeting_number' => $meeting?->meeting_number ?? ($row['meeting_number'] ?? ($index + 1)),
                'title' => $row['title'] ?? 'Modul '.($index + 1),
                'description' => $row['description'] ?? '',
                'module_drive_link' => $row['module_drive_link'] ?? '',
                'deadline' => $row['deadline'] ?? '',
                'is_published' => filter_var($row['is_published'] ?? false, FILTER_VALIDATE_BOOL),
            ];
        })->all();
    @endphp

    <div class="mb-6 rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-semibold text-emerald-900">Atur modul dan pengumpulan laprak</p>
                <p class="mt-1 text-sm leading-6 text-emerald-800/80">Kelas dapat memiliki 1–16 modul. Link materi bersifat opsional dan dapat ditambahkan nanti. Aktifkan pengumpulan agar mahasiswa dapat mengirim link laprak.</p>
            </div>
            <a href="{{ route('courses.show', $course) }}" class="shrink-0 rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-center text-sm font-semibold text-emerald-800">Lihat kelas</a>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-700" role="alert">
            <p class="font-semibold">Modul belum tersimpan. Periksa data berikut:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('courses.modules.update', $course) }}" class="space-y-6"
          x-data="moduleEditor(@js($rows))" @submit="submitting = true">
        @csrf
        @method('PUT')

        <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-semibold text-slate-800"><span x-text="modules.length"></span> modul</p>
                <p class="mt-1 text-xs text-slate-500">Modul tersimpan tidak dapat dihapus dari halaman ini agar presensi dan laprak tetap aman.</p>
            </div>
            <button type="button" @click="addModule()" :disabled="modules.length >= 16"
                    class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40">
                <span x-text="modules.length >= 16 ? 'Maksimal 16 modul' : '+ Tambah modul'"></span>
            </button>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <template x-for="(module, index) in modules" :key="module.id ?? `new-${module.meeting_number}`">
                <section class="overflow-hidden rounded-2xl border bg-white shadow-sm" :class="module.is_published ? 'border-emerald-200' : 'border-slate-200'">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 bg-slate-50/70 px-6 py-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-700" x-text="`Modul ${module.meeting_number}`"></p>
                            <h2 class="mt-1 font-semibold text-slate-900" x-text="module.is_published ? 'Pengumpulan dibuka' : 'Coming Soon'"></h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-3 py-1.5 text-xs font-semibold" :class="module.is_published ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-200/70 text-slate-600'" x-text="module.is_published ? 'Aktif' : 'Draft'"></span>
                            <button x-show="!module.id" type="button" @click="removeNewModule(index)" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50">Batalkan</button>
                        </div>
                    </div>

                    <template x-if="module.id">
                        <input type="hidden" :name="`modules[${index}][id]`" :value="module.id">
                    </template>
                    <div class="space-y-4 p-6">
                        <label class="block text-sm font-semibold text-slate-700">Judul modul
                            <input :name="`modules[${index}][title]`" x-model="module.title" maxlength="255" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/60" placeholder="Contoh: Routing dan Controller">
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">Link materi Google Drive <span class="font-normal text-slate-400">(opsional)</span>
                            <input type="url" :name="`modules[${index}][module_drive_link]`" x-model="module.module_drive_link" maxlength="2048" class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/60" placeholder="https://drive.google.com/file/d/.../view">
                            <span class="mt-2 block text-xs font-normal leading-5 text-slate-500">Materi dapat dikosongkan dan ditambahkan kapan saja tanpa menutup pengumpulan.</span>
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">Instruksi laprak <span class="font-normal text-slate-400">(opsional)</span>
                            <textarea :name="`modules[${index}][description]`" x-model="module.description" maxlength="10000" rows="4" class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/60" placeholder="Jelaskan ketentuan pengumpulan jika sudah tersedia."></textarea>
                        </label>
                        <label class="block text-sm font-semibold text-slate-700">Batas pengumpulan (WIB) <span class="font-normal text-slate-400">(opsional)</span>
                            <input type="datetime-local" :name="`modules[${index}][deadline]`" x-model="module.deadline" class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/60">
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <input type="hidden" :name="`modules[${index}][is_published]`" value="0">
                            <input type="checkbox" :name="`modules[${index}][is_published]`" value="1" x-model="module.is_published" class="mt-0.5 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">Buka pengumpulan untuk mahasiswa</span>
                                <span class="mt-1 block text-xs font-normal leading-5 text-slate-500">Mahasiswa dapat mengirim link laprak meskipun materi dan deadline belum diisi.</span>
                            </span>
                        </label>
                    </div>
                </section>
            </template>
        </div>

        <div class="sticky bottom-4 flex flex-col-reverse justify-end gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-xl backdrop-blur sm:flex-row">
            <a href="{{ route('courses.show', $course) }}" class="rounded-xl px-5 py-3 text-center text-sm font-semibold text-slate-600">Batal</a>
            <button :disabled="submitting || modules.length === 0" class="rounded-xl bg-emerald-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-50" x-text="submitting ? 'Menyimpan...' : 'Simpan semua modul'">Simpan semua modul</button>
        </div>
    </form>

    <script>
        function moduleEditor(initialModules) {
            return {
                modules: initialModules,
                submitting: false,
                addModule() {
                    if (this.modules.length >= 16) return;

                    const number = Math.max(0, ...this.modules.map(module => Number(module.meeting_number) || 0)) + 1;
                    this.modules.push({
                        id: null,
                        meeting_number: number,
                        title: `Modul ${number}`,
                        description: '',
                        module_drive_link: '',
                        deadline: '',
                        is_published: true,
                    });
                },
                removeNewModule(index) {
                    if (!this.modules[index]?.id) this.modules.splice(index, 1);
                },
            };
        }
    </script>
</x-app-layout>
