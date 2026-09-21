<x-app-layout>
    <x-slot name="header_title">Buat kelas praktikum</x-slot>

    <form action="{{ route('courses.store') }}" method="POST" class="space-y-6"
        x-data="{ submitting: false, moduleCount: {{ (int) old('module_count', 8) }} }" @submit="submitting = true">
        @csrf

        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-700" role="alert">
                <p class="font-semibold">Kelas belum tersimpan. Periksa data berikut:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if($laborans->isEmpty() || $dosens->isEmpty() || $aslabs->isEmpty())
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-800" role="alert">
                <p class="font-semibold">Data pengajar belum lengkap.</p>
                <p class="mt-1 text-sm">@if($laborans->isEmpty()) Belum ada akun Laboran yang aktif. @endif @if($dosens->isEmpty()) Belum ada akun Dosen yang aktif. @endif @if($aslabs->isEmpty()) Belum ada akun Asisten Laboratorium yang aktif. @endif</p>
            </div>
        @endif

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(340px,0.72fr)]">
            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-emerald-700">Langkah 1</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Informasi kelas</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Pilih petugas yang bertanggung jawab pada kelas. Aslab yang ditunjuk dapat mengatur modul setelah kelas dibuat.</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Nama mata kuliah
                            <input type="text" name="course_name" value="{{ old('course_name') }}" maxlength="255" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70" placeholder="Contoh: Jaringan Syaraf Tiruan">
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Kelas
                            <input type="text" name="class_group" value="{{ old('class_group') }}" maxlength="50" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70" placeholder="Contoh: IK-1">
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Semester mahasiswa
                            <input type="number" name="target_semester" value="{{ old('target_semester') }}" min="1" max="14" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70" placeholder="Contoh: 5">
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Laboran penanggung jawab
                            <select name="laboran_id" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70">
                                <option value="">Pilih laboran</option>
                                @foreach($laborans as $laboran)<option value="{{ $laboran->id }}" @selected((string) old('laboran_id', auth()->id()) === (string) $laboran->id)>{{ $laboran->name }} â€” {{ $laboran->id }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Dosen pengampu
                            <select name="dosen_id" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70">
                                <option value="">Pilih dosen</option>
                                @foreach($dosens as $dosen)<option value="{{ $dosen->id }}" @selected((string) old('dosen_id') === (string) $dosen->id)>{{ $dosen->name }} — {{ $dosen->id }}</option>@endforeach
                            </select>
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Asisten laboratorium
                            <select name="aslab_id" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70">
                                <option value="">Pilih asisten</option>
                                @foreach($aslabs as $aslab)<option value="{{ $aslab->id }}" @selected((string) old('aslab_id') === (string) $aslab->id)>{{ $aslab->name }} — {{ $aslab->id }}</option>@endforeach
                            </select>
                            <span class="mt-2 block text-xs font-normal leading-5 text-slate-500">Aslab terpilih dapat mengatur judul, materi, instruksi, dan deadline modul.</span>
                        </label>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-emerald-700">Langkah 2</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Tentukan jumlah modul</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Jumlah modul bebas dari 1 sampai 16 dan menjadi struktur tetap kelas. Modul yang materinya belum dilengkapi akan tampil sebagai Coming Soon.</p>
                    </div>

                    <div class="flex flex-col gap-5 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="text-sm font-semibold text-slate-800">Jumlah kartu modul</p><p class="mt-1 text-xs leading-5 text-slate-500">Umumnya praktikum menggunakan 6–8 modul.</p></div>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="moduleCount = Math.max(1, moduleCount - 1)" :disabled="moduleCount <= 1" class="flex h-11 w-11 items-center justify-center rounded-xl border border-emerald-200 bg-white text-xl font-semibold text-emerald-800 disabled:opacity-40" aria-label="Kurangi jumlah modul">−</button>
                            <input name="module_count" x-model.number="moduleCount" @input="moduleCount = Math.min(16, Math.max(1, Number(moduleCount) || 1))" type="number" min="1" max="16" required class="h-12 w-20 rounded-xl border-emerald-200 bg-white text-center text-lg font-bold text-emerald-800">
                            <button type="button" @click="moduleCount = Math.min(16, moduleCount + 1)" :disabled="moduleCount >= 16" class="flex h-11 w-11 items-center justify-center rounded-xl border border-emerald-200 bg-white text-xl font-semibold text-emerald-800 disabled:opacity-40" aria-label="Tambah jumlah modul">+</button>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:sticky xl:top-6">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-sm font-semibold text-emerald-700">Preview struktur</p><h2 class="mt-1 text-xl font-semibold text-slate-900"><span x-text="moduleCount"></span> modul praktikum</h2></div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">Maks. 16</span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-500">Kartu ini langsung dibuat sebagai Coming Soon. Laboran atau Aslab dapat membuka pengumpulan tanpa harus mengisi materi terlebih dahulu.</p>

                <div class="custom-scrollbar mt-6 grid max-h-[34rem] gap-3 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                    <template x-for="number in moduleCount" :key="number">
                        <article class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/70 p-4">
                            <div class="flex items-center justify-between gap-3"><span class="text-xs font-semibold text-emerald-700" x-text="`Modul ${number}`"></span><span class="rounded-full bg-slate-200/70 px-2 py-1 text-xs font-semibold text-slate-500">Coming Soon</span></div>
                            <div class="mt-5 h-2 w-3/4 rounded-full bg-slate-200"></div><div class="mt-2 h-2 w-1/2 rounded-full bg-slate-100"></div>
                        </article>
                    </template>
                </div>
            </aside>
        </div>

        <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
            <a href="{{ route('courses.index') }}" class="rounded-xl px-5 py-3 text-center text-sm font-semibold text-slate-600">Batal</a>
            <button type="submit" :disabled="submitting || {{ $laborans->isEmpty() || $dosens->isEmpty() || $aslabs->isEmpty() ? 'true' : 'false' }}" class="rounded-xl bg-emerald-700 px-6 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-50"><span x-text="submitting ? 'Membuat kelas...' : `Buat kelas dengan ${moduleCount} modul`"></span></button>
        </div>
    </form>
</x-app-layout>
