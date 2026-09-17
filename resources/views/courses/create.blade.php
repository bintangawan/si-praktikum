<x-app-layout>
    <x-slot name="header_title">
        Buat Kelas Praktikum
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
        <div class="p-6 bg-white border-b border-gray-200">
            @if($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-700" role="alert">
                    <p class="text-sm font-bold">Kelas belum tersimpan. Periksa data berikut:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($dosens->isEmpty() || $aslabs->isEmpty())
                <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800" role="alert">
                    <p class="text-sm font-bold">Data pengajar belum lengkap.</p>
                    <p class="mt-1 text-sm">
                        @if($dosens->isEmpty()) Belum ada akun Dosen. @endif
                        @if($aslabs->isEmpty()) Belum ada akun Asisten Laboratorium. @endif
                        Tambahkan pengguna yang diperlukan sebelum membuat kelas.
                    </p>
                </div>
            @endif

            <form action="{{ route('courses.store') }}" method="POST" x-data="{ submitting: false }" @submit="submitting = true">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Nama Mata Kuliah</label>
                        <input type="text" name="course_name" value="{{ old('course_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: Jaringan Syaraf Tiruan" maxlength="255" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kelas</label>
                        <input type="text" name="class_group" value="{{ old('class_group') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm uppercase" placeholder="Contoh: IK-1" maxlength="50" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Semester (Mahasiswa)</label>
                        <input type="number" name="target_semester" value="{{ old('target_semester') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Contoh: 5" min="1" max="14" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dosen Pengampu</label>
                        <select name="dosen_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            <option value="">Pilih Dosen</option>
                            @foreach($dosens as $dosen)
                                <option value="{{ $dosen->id }}" @selected(old('dosen_id') === $dosen->id)>{{ $dosen->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Asisten Laboratorium yang Ditugaskan</label>
                        <select name="aslab_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            <option value="">Pilih Asisten</option>
                            @foreach($aslabs as $aslab)
                                <option value="{{ $aslab->id }}" @selected(old('aslab_id') === $aslab->id)>{{ $aslab->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500">Aslab dipilih oleh Laboran dan hanya akan mengelola kelas yang ditugaskan kepadanya.</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" 
                            :disabled="submitting"
                            @disabled($dosens->isEmpty() || $aslabs->isEmpty())
                            class="inline-flex min-w-52 items-center justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg x-show="submitting" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        <span x-text="submitting ? 'Menyimpan...' : 'Simpan & Generate Kode'">Simpan & Generate Kode</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
