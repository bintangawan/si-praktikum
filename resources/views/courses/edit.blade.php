<x-app-layout>
    <x-slot name="header_title">Edit kelas praktikum</x-slot>

    <form action="{{ route('courses.update', $course) }}" method="POST" class="mx-auto max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-6">
                <p class="text-sm font-semibold text-emerald-700">Informasi kelas</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Perbaiki data kelas</h1>
                <p class="mt-2 text-sm text-slate-500">Modul, peserta, presensi, dan laprak tidak berubah ketika informasi kelas diperbarui.</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <label class="md:col-span-2 text-sm font-semibold text-slate-700">Nama mata kuliah
                    <input name="course_name" value="{{ old('course_name', $course->course_name) }}" maxlength="255" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70">
                    <x-input-error :messages="$errors->get('course_name')" class="mt-2" />
                </label>
                <label class="text-sm font-semibold text-slate-700">Kelompok kelas
                    <input name="class_group" value="{{ old('class_group', $course->class_group) }}" maxlength="50" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70">
                    <x-input-error :messages="$errors->get('class_group')" class="mt-2" />
                </label>
                <label class="text-sm font-semibold text-slate-700">Semester mahasiswa
                    <input type="number" name="target_semester" value="{{ old('target_semester', $course->target_semester) }}" min="1" max="14" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70">
                    <x-input-error :messages="$errors->get('target_semester')" class="mt-2" />
                </label>
                <label class="text-sm font-semibold text-slate-700">Dosen pengampu
                    <select name="dosen_id" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70">
                        @foreach($dosens as $dosen)
                            <option value="{{ $dosen->id }}" @selected((string) old('dosen_id', $course->dosen_id) === (string) $dosen->id)>{{ $dosen->name }} — {{ $dosen->id }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('dosen_id')" class="mt-2" />
                </label>
                <label class="text-sm font-semibold text-slate-700">Asisten laboratorium
                    <select name="aslab_id" required class="mt-2 block w-full rounded-xl border-slate-200 bg-slate-50/70">
                        @foreach($aslabs as $aslab)
                            <option value="{{ $aslab->id }}" @selected((string) old('aslab_id', $course->aslab_id) === (string) $aslab->id)>{{ $aslab->name }} — {{ $aslab->id }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('aslab_id')" class="mt-2" />
                </label>
            </div>
        </section>

        <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
            <a href="{{ route('courses.index') }}" class="rounded-xl px-5 py-3 text-center text-sm font-semibold text-slate-600">Batal</a>
            <button class="w-full rounded-xl bg-emerald-700 px-6 py-3 text-sm font-semibold text-white shadow-lg hover:bg-emerald-800 sm:w-auto">Simpan perubahan</button>
        </div>
    </form>
</x-app-layout>
