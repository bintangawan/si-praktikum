@if(in_array(strtoupper(auth()->user()->role), ['DOSEN', 'ASLAB', 'LABORAN']))
{{-- Modal Tambah Pertemuan --}}
<div id="modalMeeting" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in duration-300">
        <form action="{{ route('meetings.store', $course->id) }}" method="POST">
            @csrf
            <div class="p-10 border-b border-gray-50 bg-gray-50/50">
                <h3 class="text-2xl font-black text-gray-800 leading-tight tracking-tight">Tambah Pertemuan</h3>
            </div>
            <div class="p-10 space-y-6">
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2.5">Pertemuan Ke-</label>
                    <input type="number" name="meeting_number" required min="1" value="{{ $course->meetings->count() + 1 }}" class="block w-full rounded-2xl border-gray-100 text-sm font-black focus:ring-4 focus:ring-indigo-50 p-4 bg-gray-50 transition">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2.5">Judul Materi</label>
                    <input type="text" name="title" required placeholder="Contoh: Dasar Dasar PHP" class="block w-full rounded-2xl border-gray-100 text-sm focus:ring-4 focus:ring-indigo-50 p-4 bg-gray-50 transition">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2.5">Link Modul (G-Drive)</label>
                    <input type="url" name="module_drive_link" placeholder="https://..." class="block w-full rounded-2xl border-gray-100 text-sm focus:ring-4 focus:ring-indigo-50 p-4 bg-gray-50 transition text-indigo-600">
                </div>
            </div>
            <div class="p-10 pt-0 bg-white flex flex-col gap-4">
                <button type="submit" class="w-full bg-indigo-600 text-white py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition">Simpan Materi</button>
                <button type="button" onclick="closeMeetingModal()" class="w-full text-[9px] font-black text-gray-400 uppercase tracking-widest hover:text-red-500 transition">Batalkan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Buat Laprak Final --}}
<div id="modal-final-task" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden">
        <form action="{{ route('final-tasks.store', $course->id) }}" method="POST">
            @csrf
            <div class="p-10 border-b border-gray-50 bg-indigo-50/30">
                <h3 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Setup Laprak Final</h3>
                <p class="text-[9px] font-bold text-indigo-400 uppercase tracking-widest mt-1">Laporan Akhir Praktikum Semester</p>
            </div>
            <div class="p-10 space-y-6">
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2.5">Instruksi / Deskripsi Tugas</label>
                    <textarea name="description" rows="4" class="w-full rounded-2xl border-gray-100 bg-gray-50 text-sm p-4 focus:ring-4 focus:ring-indigo-50 outline-none resize-none" placeholder="Tuliskan instruksi pengerjaan di sini..." required></textarea>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2.5">Batas Waktu (Deadline)</label>
                    <input type="datetime-local" name="deadline" class="w-full rounded-2xl border-gray-100 bg-gray-50 text-sm p-4 focus:ring-4 focus:ring-indigo-50 outline-none">
                </div>
            </div>
            <div class="p-10 pt-0 bg-white flex flex-col sm:flex-row gap-4">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition">Simpan & Publikasi</button>
                <button type="button" onclick="document.getElementById('modal-final-task').classList.replace('flex', 'hidden')" class="flex-1 text-[9px] font-black text-gray-400 uppercase tracking-widest hover:text-red-500 transition">Batal</button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
    function openMeetingModal() {
        document.getElementById('modalMeeting').classList.replace('hidden', 'flex');
        document.body.style.overflow = 'hidden';
    }
    function closeMeetingModal() {
        document.getElementById('modalMeeting').classList.replace('flex', 'hidden');
        document.body.style.overflow = 'auto';
    }
</script>
