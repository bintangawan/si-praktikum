<x-app-layout>
    <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-2xl font-black text-emerald-950 tracking-tight">Tutorial & Panduan</h2>
            <p class="text-xs text-gray-500 font-medium">Kumpulan materi instruksional berupa dokumen PDF dan video demonstrasi.</p>
        </div>

        {{-- FORM MODAL TOMBOL (Hanya Muncul Jika Active Role Laboran) --}}
        @if(strtoupper(auth()->user()->role) === 'LABORAN')
            <div x-data="{ modalOpen: false }">
                <button @click="modalOpen = true" class="px-4 py-2.5 bg-emerald-600 text-white text-xs font-bold rounded-xl hover:bg-emerald-700 transition flex items-center gap-2 shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Tutorial
                </button>

                {{-- MODAL TAMBAH TUTORIAL --}}
                <div x-show="modalOpen" @click.away="modalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4" style="display: none;">
                    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100">
                        <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-100">
                            <h3 class="text-base font-black text-gray-800">Unggah Tutorial Baru</h3>
                            <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <form action="{{ route('tutorials.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Judul Tutorial</label>
                                <input type="text" name="title" required class="w-full text-xs rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: Tata Cara Pembuatan Laprak">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Tipe Konten</label>
                                <select name="type" required class="w-full text-xs rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="youtube">Video YouTube</option>
                                    <option value="gdrive_pdf">Google Drive (PDF / Dokumen)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">URL / Link</label>
                                <input type="url" name="url" required class="w-full text-xs rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500" placeholder="https://www.youtube.com/watch?v=... atau link Drive">
                                <span class="text-[10px] text-gray-400">Pastikan tautan Google Drive disetel ke "Siapa saja yang memiliki link".</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Deskripsi Singkat</label>
                                <textarea name="description" rows="3" class="w-full text-xs rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Catatan singkat terkait tutorial ini..."></textarea>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-xs font-bold hover:bg-gray-200 transition">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 transition">Simpan Tutorial</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- KONTROL PENCARIAN & FILTER --}}
    <form method="GET" action="{{ route('tutorials.index') }}" class="mb-6 bg-white p-4 rounded-2xl shadow-sm border border-emerald-100 flex flex-col md:flex-row gap-3 items-center justify-between">
        {{-- INPUT PENCARIAN --}}
        <div class="relative w-full md:w-1/2">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input type="text" name="search" value="{{ request('search') }}" 
                   class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500" 
                   placeholder="Cari berdasarkan judul atau deskripsi tutorial...">
        </div>

        {{-- FILTER TIPE KONTEN & TOMBOL --}}
        <div class="flex items-center gap-2 w-full md:w-auto">
            <select name="type" class="w-full md:w-auto text-xs rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 py-2">
                <option value="">Semua Tipe</option>
                <option value="youtube" {{ request('type') === 'youtube' ? 'selected' : '' }}>Video YouTube</option>
                <option value="gdrive_pdf" {{ request('type') === 'gdrive_pdf' ? 'selected' : '' }}>Google Drive (PDF)</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-amber-500 text-slate-900 text-xs font-black rounded-xl hover:bg-amber-400 transition shadow-sm flex items-center gap-1 shrink-0">
                Cari
            </button>

            @if(request('search') || request('type'))
                <a href="{{ route('tutorials.index') }}" class="px-3 py-2 bg-gray-100 text-gray-600 text-xs font-bold rounded-xl hover:bg-gray-200 transition shrink-0" title="Reset Filter">
                    Reset
                </a>
            @endif
        </div>
    </form>

    {{-- DAFTAR TUTORIAL --}}
    @if($tutorials->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center border border-gray-100 shadow-sm">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            <p class="text-sm font-bold text-gray-500">
                @if(request('search') || request('type'))
                    Tidak ditemukan tutorial yang cocok dengan kata kunci/filter Anda.
                @else
                    Belum ada tutorial yang tersedia saat ini.
                @endif
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($tutorials as $tutorial)
                <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 overflow-hidden flex flex-col justify-between">
                    <div>
                        {{-- PLAYER EMBED (YouTube atau Iframe PDF Drive) --}}
                        <div class="aspect-video w-full bg-black">
                            <iframe src="{{ $tutorial->embed_url }}" 
                                    class="w-full h-full border-0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        </div>

                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-2">
                                @if($tutorial->type === 'youtube')
                                    <span class="px-2 py-0.5 bg-red-50 text-red-600 text-[10px] font-black rounded-md uppercase tracking-wider">YouTube</span>
                                @else
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-black rounded-md uppercase tracking-wider">PDF Drive</span>
                                @endif
                                <span class="text-[10px] text-gray-400 font-mono">{{ $tutorial->created_at->format('d M Y') }}</span>
                            </div>

                            <h3 class="font-bold text-gray-800 text-base mb-1">{{ $tutorial->title }}</h3>
                            <p class="text-xs text-gray-500 leading-relaxed">{{ $tutorial->description ?? 'Tidak ada deskripsi tambahan.' }}</p>
                        </div>
                    </div>

                    <div class="px-5 py-3 bg-emerald-50/20 border-t border-emerald-100 flex items-center justify-between">
                        <span class="text-[10px] text-gray-400">Oleh: <strong class="text-gray-600">{{ $tutorial->author->name }}</strong></span>
                        
                        <div class="flex items-center gap-2">
                            <a href="{{ $tutorial->url }}" target="_blank" class="text-xs font-bold text-emerald-700 hover:text-emerald-900 transition">Buka Sumber asli &rarr;</a>
                            
                            @if(strtoupper(auth()->user()->role) === 'LABORAN')
                                <form action="{{ route('tutorials.destroy', $tutorial->id) }}" method="POST" onsubmit="return confirm('Hapus tutorial ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 ml-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
