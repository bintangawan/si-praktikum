<x-app-layout>
    <x-slot name="header_title">Buat kelas dan modul praktikum</x-slot>

    @php
        $initialModules = old('modules', [[
            'meeting_number' => 1,
            'title' => 'Modul 1',
            'description' => '',
            'module_drive_link' => '',
            'deadline' => '',
        ]]);
    @endphp

    <form
        action="{{ route('courses.store') }}"
        method="POST"
        class="space-y-6"
        x-data="{
            submitting: false,
            modules: @js($initialModules),
            addModule() {
                if (this.modules.length >= 50) return;
                const number = this.modules.length + 1;
                this.modules.push({ meeting_number: number, title: `Modul ${number}`, description: '', module_drive_link: '', deadline: '' });
            },
            removeModule(index) {
                if (this.modules.length === 1) return;
                this.modules.splice(index, 1);
                this.modules.forEach((module, position) => {
                    module.meeting_number = position + 1;
                    if (/^Modul \d+$/.test(module.title || '')) module.title = `Modul ${position + 1}`;
                });
            }
        }"
        @submit="submitting = true"
    >
        @csrf

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-700" role="alert">
                <p class="font-semibold">Kelas belum tersimpan. Periksa data berikut:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($dosens->isEmpty() || $aslabs->isEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800" role="alert">
                <p class="font-semibold">Data pengajar belum lengkap.</p>
                <p class="mt-1 text-sm">
                    @if($dosens->isEmpty()) Belum ada akun Dosen yang aktif. @endif
                    @if($aslabs->isEmpty()) Belum ada akun Asisten Laboratorium yang aktif. @endif
                </p>
            </div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <p class="text-sm font-semibold text-emerald-700">Langkah 1</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900">Informasi kelas</h2>
                <p class="mt-2 text-sm text-slate-500">Kode enrollment dibuat otomatis setelah kelas dan seluruh modul berhasil disimpan.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <label class="md:col-span-2 text-sm font-semibold text-slate-700">Nama mata kuliah
                    <input type="text" name="course_name" value="{{ old('course_name') }}" maxlength="255" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70" placeholder="Contoh: Jaringan Syaraf Tiruan">
                </label>
                <label class="text-sm font-semibold text-slate-700">Kelas
                    <input type="text" name="class_group" value="{{ old('class_group') }}" maxlength="50" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70" placeholder="Contoh: IK-1">
                </label>
                <label class="text-sm font-semibold text-slate-700">Semester mahasiswa
                    <input type="number" name="target_semester" value="{{ old('target_semester') }}" min="1" max="14" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70" placeholder="Contoh: 5">
                </label>
                <label class="text-sm font-semibold text-slate-700">Dosen pengampu
                    <select name="dosen_id" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70">
                        <option value="">Pilih dosen</option>
                        @foreach($dosens as $dosen)<option value="{{ $dosen->id }}" @selected(old('dosen_id') === $dosen->id)>{{ $dosen->name }} — {{ $dosen->id }}</option>@endforeach
                    </select>
                </label>
                <label class="text-sm font-semibold text-slate-700">Asisten laboratorium
                    <select name="aslab_id" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70">
                        <option value="">Pilih asisten</option>
                        @foreach($aslabs as $aslab)<option value="{{ $aslab->id }}" @selected(old('aslab_id') === $aslab->id)>{{ $aslab->name }} — {{ $aslab->id }}</option>@endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-emerald-700">Langkah 2</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">Modul praktikum</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Minimal satu modul wajib dibuat. Setiap modul otomatis menjadi satu kartu dan satu tempat upload laprak yang berbeda untuk mahasiswa.</p>
                </div>
                <button type="button" @click="addModule()" :disabled="modules.length >= 50" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 disabled:opacity-40">Tambah modul</button>
            </div>

            <div class="grid gap-5 xl:grid-cols-2">
                <template x-for="(module, index) in modules" :key="index">
                    <article class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <div><p class="text-xs font-semibold text-emerald-700" x-text="`Modul ${index + 1}`"></p><h3 class="mt-1 font-semibold text-slate-900">Materi dan tugas laprak</h3></div>
                            <button type="button" @click="removeModule(index)" x-show="modules.length > 1" class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Hapus</button>
                        </div>

                        <input type="hidden" :name="`modules[${index}][meeting_number]`" :value="index + 1">
                        <div class="space-y-4">
                            <label class="block text-sm font-semibold text-slate-700">Judul modul
                                <input type="text" :name="`modules[${index}][title]`" x-model="module.title" maxlength="255" required class="mt-2 block w-full rounded-xl border-slate-200 bg-white" :placeholder="`Contoh: Modul ${index + 1} — Pengenalan`">
                            </label>
                            <label class="block text-sm font-semibold text-slate-700">Link materi Google Drive
                                <input type="url" :name="`modules[${index}][module_drive_link]`" x-model="module.module_drive_link" maxlength="2048" required class="mt-2 block w-full rounded-xl border-slate-200 bg-white" placeholder="https://drive.google.com/file/d/.../view">
                                <span class="mt-2 block text-xs font-normal leading-5 text-slate-500">Gunakan link file Drive yang dapat dibaca mahasiswa, bukan link folder.</span>
                            </label>
                            <label class="block text-sm font-semibold text-slate-700">Instruksi laprak
                                <textarea :name="`modules[${index}][description]`" x-model="module.description" maxlength="10000" rows="3" class="mt-2 block w-full rounded-xl border-slate-200 bg-white" placeholder="Jelaskan pekerjaan dan ketentuan laprak untuk modul ini."></textarea>
                            </label>
                            <label class="block text-sm font-semibold text-slate-700">Batas pengumpulan (WIB)
                                <input type="datetime-local" :name="`modules[${index}][deadline]`" x-model="module.deadline" class="mt-2 block w-full rounded-xl border-slate-200 bg-white">
                            </label>
                        </div>
                    </article>
                </template>
            </div>
            <p class="mt-5 text-sm text-slate-500"><span x-text="modules.length"></span> modul akan dibuat bersama kelas.</p>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ route('courses.index') }}" class="rounded-xl px-5 py-3 text-sm font-semibold text-slate-600">Batal</a>
            <button type="submit" :disabled="submitting" @disabled($dosens->isEmpty() || $aslabs->isEmpty()) class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-lg hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto sm:min-w-56">
                <svg x-show="submitting" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                <span x-text="submitting ? 'Menyimpan kelas...' : `Buat kelas & ${modules.length} modul`">Buat kelas dan modul</span>
            </button>
        </div>
    </form>
</x-app-layout>
