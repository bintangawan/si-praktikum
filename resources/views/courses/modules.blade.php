<x-app-layout>
    <x-slot name="header_title">Kelola modul: {{ $course->course_name }}</x-slot>
    @php
        $rows = old('modules', $course->meetings->map(fn ($m) => ['meeting_number' => $m->meeting_number, 'title' => $m->title, 'description' => $m->description, 'module_drive_link' => $m->module_drive_link, 'deadline' => $m->deadline?->format('Y-m-d\TH:i')])->all());
        if (!$rows) $rows = collect(range(1, 8))->map(fn ($n) => ['meeting_number' => $n, 'title' => 'Modul '.$n, 'description' => '', 'module_drive_link' => '', 'deadline' => ''])->all();
    @endphp
    <p class="mb-6 text-sm text-slate-600">Satu modul adalah satu pertemuan dan satu tempat pengumpulan. Lengkapi link materi Drive setiap modul. Modul yang sudah ada diperbarui tanpa menghapus pengumpulan mahasiswa.</p>
    @if($errors->any())<div class="mb-5 rounded-xl bg-red-50 p-4 text-sm text-red-700" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('courses.modules.store', $course) }}" x-data="{
        rows: @js($rows),
        add() {
            if (this.rows.length >= 50) return;
            const number = this.rows.length + 1;
            this.rows.push({meeting_number:number,title:'Modul '+number,description:'',module_drive_link:'',deadline:''});
        },
        remove(index) {
            if (this.rows.length === 1) return;
            this.rows.splice(index, 1);
            this.rows.forEach((row, position) => {
                row.meeting_number = position + 1;
                if (/^Modul \d+$/.test(row.title || '')) row.title = 'Modul ' + (position + 1);
            });
        }
    }">
        @csrf
        <div class="grid gap-5 xl:grid-cols-2">
            <template x-for="(row, index) in rows" :key="index"><section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-emerald-800" x-text="'Modul ' + row.meeting_number"></h2>
                <div class="grid grid-cols-[5rem_1fr] gap-3"><label class="text-sm">Nomor<input class="mt-2 w-full rounded-xl border-slate-200" type="number" readonly min="1" max="16" :name="`modules[${index}][meeting_number]`" x-model="row.meeting_number" required></label><label class="text-sm">Judul<input class="mt-2 w-full rounded-xl border-slate-200" :name="`modules[${index}][title]`" x-model="row.title" maxlength="255" required></label></div>
                <label class="block text-sm">Link materi Google Drive<input class="mt-2 w-full rounded-xl border-slate-200" type="url" :name="`modules[${index}][module_drive_link]`" x-model="row.module_drive_link" placeholder="https://drive.google.com/file/d/.../view" required></label>
                <label class="block text-sm">Instruksi<textarea class="mt-2 w-full rounded-xl border-slate-200" :name="`modules[${index}][description]`" x-model="row.description" maxlength="10000" rows="3"></textarea></label>
                <label class="block text-sm">Batas pengumpulan (WIB)<input class="mt-2 w-full rounded-xl border-slate-200" type="datetime-local" :name="`modules[${index}][deadline]`" x-model="row.deadline"></label>
                <button type="button" @click="remove(index)" x-show="rows.length > 1" class="text-sm font-semibold text-red-600">Hapus baris dari formulir</button>
            </section></template>
        </div>
        <div class="mt-6 flex flex-wrap gap-3"><button type="button" @click="add()" :disabled="rows.length >= 50" class="rounded-xl border border-emerald-200 px-5 py-3 text-sm font-semibold text-emerald-800">Tambah modul</button><button @disabled(!$course->semester->is_active) class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white disabled:opacity-40">Simpan dan publikasikan</button><a href="{{ route('courses.show', $course) }}" class="px-4 py-3 text-sm text-slate-600">Kembali</a></div>
    </form>
</x-app-layout>
