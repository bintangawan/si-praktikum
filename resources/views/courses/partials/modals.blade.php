@if(in_array(strtoupper(auth()->user()->role), ['DOSEN', 'ASLAB', 'LABORAN']))
{{-- Modal Tambah Pertemuan --}}
<div id="modalMeeting" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto animate-in zoom-in duration-300">
        <form action="{{ route('meetings.store', $course) }}" method="POST">
            @csrf
            <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                <h3 class="text-2xl font-semibold text-gray-800 leading-tight tracking-tight">Tambah Pertemuan</h3>
            </div>
            <div class="p-6 space-y-6">
                @if($errors->any())<div class="text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                <div>
                    <label class="block text-xs font-semibold text-gray-400 tracking-normal mb-2.5">Pertemuan Ke-</label>
                    <input type="number" name="meeting_number" required min="1" max="16" value="{{ old('meeting_number', ($course->meetings->max('meeting_number') ?? 0) + 1) }}" class="block w-full rounded-2xl border-gray-100 text-sm font-semibold focus:ring-4 focus:ring-emerald-50 p-4 bg-gray-50 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 tracking-normal mb-2.5">Judul Materi</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="Contoh: Dasar Dasar PHP" class="block w-full rounded-2xl border-gray-100 text-sm focus:ring-4 focus:ring-emerald-50 p-4 bg-gray-50 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 tracking-normal mb-2.5">Link Modul (G-Drive)</label>
                    <input type="url" name="module_drive_link" value="{{ old('module_drive_link') }}" required placeholder="https://..." class="block w-full rounded-2xl border-gray-100 text-sm focus:ring-4 focus:ring-emerald-50 p-4 bg-gray-50 transition text-emerald-600">
                </div>
            </div>
            <div class="px-6 pb-5 space-y-4"><label class="block text-sm">Instruksi<textarea name="description" class="mt-2 w-full rounded-xl border-slate-200">{{ old('description') }}</textarea></label><label class="block text-sm">Deadline (WIB)<input name="deadline" type="datetime-local" value="{{ old('deadline') }}" class="mt-2 w-full rounded-xl border-slate-200"></label></div>
            <div class="p-6 pt-0 bg-white flex flex-col gap-4">
                <button type="submit" class="w-full bg-emerald-600 text-white py-5 rounded-2xl text-xs font-semibold tracking-normal shadow-xl shadow-emerald-100 hover:bg-emerald-700 transition">Simpan Materi</button>
                <button type="button" onclick="closeMeetingModal()" class="w-full text-xs font-semibold text-gray-400 tracking-normal hover:text-red-500 transition">Batalkan</button>
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

@if($errors->has('meeting_number') || $errors->has('title') || $errors->has('module_drive_link') || ($errors->has('deadline') && old('meeting_number')))
<script>document.addEventListener('DOMContentLoaded', () => openMeetingModal());</script>
@endif
