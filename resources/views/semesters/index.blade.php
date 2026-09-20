<x-app-layout>
    <x-slot name="header_title">Manajemen Semester</x-slot>

    <div class="max-w-[95rem] mx-auto py-0 px-4">
        

        {{-- HEADER SECTION --}}
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6 mb-8">
            <div>
                <h2 class="text-3xl font-semibold text-gray-800 tracking-tight leading-none">Manajemen Semester</h2>
                <p class="text-xs font-bold text-gray-400 mt-2 tracking-normal">Kelola Periode Akademik Praktikum</p>
            </div>

            {{-- FORM TAMBAH SEMESTER --}}
            <div class="w-full lg:w-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex-shrink-0">
                <form action="{{ route('semesters.store') }}" method="POST" class="flex flex-col sm:flex-row items-end gap-3">
                    @csrf
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-semibold text-gray-400 tracking-normal mb-2 ml-1">Nama Semester Baru</label>
                        <input type="text" name="name" placeholder="Cth: Genap 2025/2026" class="w-full rounded-xl border-gray-200 focus:ring-emerald-600 focus:border-emerald-600 text-xs font-bold text-gray-600 bg-gray-50/50 sm:min-w-[250px]" required>
                    </div>
                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-slate-900 text-white text-xs font-semibold rounded-xl tracking-normal hover:bg-slate-800 transition active:scale-95 shadow-lg shadow-slate-100">
                        Tambah
                    </button>
                </form>
            </div>
        </div>

        {{-- TABEL SEMESTER --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-[760px] w-full text-left border-collapse">
                    <thead class="bg-gray-50/50 text-xs font-semibold text-gray-400 border-b border-gray-100">
                        <tr>
                            <th class="px-8 py-6 tracking-normal whitespace-nowrap">Status</th>
                            <th class="px-8 py-6 tracking-normal whitespace-nowrap">Nama Semester</th>
                            <th class="px-8 py-6 tracking-normal text-center whitespace-nowrap">Dibuat Pada</th>
                            <th class="px-8 py-6 tracking-normal text-center whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($semesters as $semester)
                            <tr class="hover:bg-gray-50/50 transition-all group {{ $semester->is_active ? 'bg-emerald-50/30' : '' }}">
                                
                                {{-- KOLOM STATUS (SAKLAR) --}}
                                <td class="px-8 py-5 whitespace-nowrap">
                                    @if($semester->is_active)
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-600 text-xs font-semibold rounded-lg tracking-normal border border-emerald-100 shadow-sm">
                                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                            Sedang Aktif
                                        </span>
                                    @else
                                        <form action="{{ route('semesters.set-active', $semester->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" onclick="return confirm('Aktifkan semester ini? Semua kelas praktikum akan berpusat pada semester ini.')" class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-50 text-gray-400 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 text-xs font-semibold rounded-lg tracking-normal border border-gray-200 transition-all">
                                                Set Aktif
                                            </button>
                                        </form>
                                    @endif
                                </td>

                                {{-- KOLOM NAMA --}}
                                <td class="px-8 py-5 whitespace-nowrap">
                                    <span class="text-gray-800 font-semibold block text-sm {{ $semester->is_active ? 'text-emerald-700' : '' }}">{{ $semester->name }}</span>
                                </td>

                                {{-- KOLOM TANGGAL --}}
                                <td class="px-8 py-5 text-center whitespace-nowrap">
                                    <span class="text-xs text-gray-400 font-bold tracking-normal">{{ $semester->created_at->format('d M Y') }}</span>
                                </td>

                                {{-- KOLOM AKSI (EDIT & DELETE) --}}
                                <td class="px-8 py-5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        {{-- Tombol Edit (Buka Modal) --}}
                                        <button onclick="openEditModal({{ $semester->id }}, '{{ $semester->name }}')" class="p-2 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-100 transition shadow-sm border border-amber-100" title="Edit Nama">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>

                                        {{-- Tombol Hapus (Disabled jika sedang aktif) --}}
                                        @if(!$semester->is_active)
                                        <form action="{{ route('semesters.destroy', $semester->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Yakin ingin menghapus semester ini?')" class="p-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition shadow-sm border border-red-100" title="Hapus Semester">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                        @else
                                        <button disabled class="p-2 bg-gray-50 text-gray-300 rounded-xl border border-gray-100 cursor-not-allowed" title="Tidak dapat menghapus semester aktif">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-16 text-center">
                                    <div class="inline-flex p-6 bg-slate-50 rounded-[2rem] text-slate-300 mb-4">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <p class="text-xs font-semibold text-gray-400 tracking-normal">Belum ada data semester.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT SEMESTER --}}
    <div id="modalEditSemester" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden animate-in zoom-in duration-300">
            <form id="editSemesterForm" method="POST">
                @csrf
                @method('PUT')
                <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                    <h3 class="text-2xl font-semibold text-gray-800 leading-tight tracking-tight">Edit Semester</h3>
                </div>
                <div class="p-6 space-y-6 text-left">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 tracking-normal mb-2.5 ml-1">Nama Semester</label>
                        <input type="text" name="name" id="editSemesterName" required class="block w-full rounded-2xl border-gray-100 text-sm font-bold focus:ring-4 focus:ring-emerald-50 p-4 bg-gray-50 transition">
                    </div>
                </div>
                <div class="p-6 pt-0 flex flex-col gap-4">
                    <button type="submit" class="w-full bg-emerald-600 text-white py-5 rounded-2xl text-xs font-semibold tracking-normal shadow-xl shadow-emerald-100 hover:bg-emerald-700 transition">Simpan Perubahan</button>
                    <button type="button" onclick="closeEditModal()" class="w-full text-xs font-semibold text-gray-400 tracking-normal hover:text-red-500 transition">Batalkan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name) {
            const form = document.getElementById('editSemesterForm');
            const inputName = document.getElementById('editSemesterName');
            
            // Set action URL secara dinamis
            form.action = `/semesters/${id}`;
            // Isi input dengan nama yang sekarang
            inputName.value = name;
            
            document.getElementById('modalEditSemester').classList.replace('hidden', 'flex');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('modalEditSemester').classList.replace('flex', 'hidden');
            document.body.style.overflow = 'auto';
        }
    </script>
</x-app-layout>
