<x-app-layout>
    <x-slot name="header_title">
        Review Tugas: {{ $submission->student->name }}
    </x-slot>

    <div class="max-w-[98rem] mx-auto py-0 px-4">
        {{-- Navigasi & Status Bar --}}
        <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <a href="{{ route('submissions.index', $submission->meeting_id) }}" 
               class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] hover:text-indigo-600 transition flex items-center group">
                <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Monitoring
            </a>
            
            <div class="flex items-center gap-6 bg-white px-6 py-3 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex flex-col">
                    <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Status Aslab</span>
                    <span class="text-[10px] font-bold {{ strtoupper($submission->aslab_status) === 'ACC' ? 'text-emerald-600' : 'text-amber-500' }} uppercase">
                        {{ $submission->aslab_status ?? 'PENDING' }}
                    </span>
                </div>
                <div class="w-px h-6 bg-gray-100"></div>
                <div class="flex flex-col">
                    <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Status Laboran</span>
                    <span class="text-[10px] font-bold {{ strtoupper($submission->laboran_status) === 'ACC' ? 'text-emerald-600' : 'text-amber-500' }} uppercase">
                        {{ $submission->laboran_status ?? 'PENDING' }}
                    </span>
                </div>
                @if($submission->is_completed)
                <div class="ml-2">
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-[9px] font-black rounded-lg uppercase border border-emerald-200">
                        COMPLETED
                    </span>
                </div>
                @endif
            </div>
        </div>

        {{-- Container Utama --}}
        <div id="main_layout_container" class="flex flex-col lg:flex-row gap-8 h-[calc(100vh-180px)] relative">
            
            {{-- PANEL PREVIEW (Kiri) --}}
            <div id="preview_panel" class="lg:w-2/3 bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden flex flex-col relative transition-all duration-500">
                <div id="preview_header" class="px-8 py-5 border-b border-gray-50 flex justify-between items-center bg-gray-50/30">
                    <div class="flex items-center gap-4">
                        <span class="px-3 py-1 bg-indigo-600 text-white text-[9px] font-black rounded-lg uppercase tracking-widest shadow-lg shadow-indigo-100">
                            PREVIEW MODE
                        </span>
                        <span id="preview_title" class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Google Drive Document</span>
                    </div>
                    <div id="compare_badge" class="hidden px-4 py-1.5 bg-amber-500 text-white text-[9px] font-black rounded-full uppercase tracking-widest animate-pulse shadow-lg shadow-amber-100">
                        Comparing Mode: Split Screen
                    </div>
                </div>

                <div id="preview_wrapper" class="flex-1 p-6 bg-slate-50 relative custom-scrollbar overflow-hidden">
                    {{-- Loading State --}}
                    <div id="placeholder_screen" class="absolute inset-0 z-10 flex flex-col items-center justify-center text-center bg-slate-50/80 backdrop-blur-sm">
                        <div class="w-12 h-12 border-4 border-indigo-100 border-t-indigo-600 rounded-full animate-spin mb-4"></div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">Menyiapkan Dokumen...</p>
                    </div>

                    {{-- Konten Utama --}}
                    <div id="dynamic_content" class="w-full h-full rounded-2xl overflow-hidden shadow-2xl shadow-slate-200"></div>
                </div>
            </div>

            {{-- PANEL ACTION (Kanan) --}}
            <div id="form_panel" class="lg:w-1/3 flex flex-col gap-6 overflow-y-auto pr-2 custom-scrollbar pb-10">
                
                {{-- Card Info Mahasiswa --}}
                <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 font-black text-lg shadow-inner">
                            {{ strtoupper(substr($submission->student->name, 0, 2)) }}
                        </div>
                        <div>
                            <h4 class="text-lg font-black text-gray-800 leading-none uppercase">{{ $submission->student->name }}</h4>
                            <p class="text-[10px] font-mono text-gray-400 mt-2">{{ $submission->student->id }}</p>
                        </div>
                    </div>

                <div class="flex flex-col sm:flex-row justify-center items-stretch gap-3 mt-6 w-full">
                    {{-- Button Refresh --}}
                    <button type="button" onclick="handleLivePreview()" 
                        class="flex-1 inline-flex items-center justify-center gap-3 px-6 py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition shadow-xl shadow-slate-200 active:scale-95 group min-h-[56px]">
                        <svg class="w-4 h-4 transform group-hover:rotate-180 transition duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>Refresh</span>
                    </button>

                    {{-- Button Periksa Laprak (External Link) --}}
                    <a href="{{ $submission->submission_link }}" target="_blank"
                        class="flex-1 inline-flex items-center justify-center gap-3 px-6 py-4 bg-emerald-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-700 transition shadow-xl shadow-emerald-100 active:scale-95 group min-h-[56px]">
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 group-hover:-translate-y-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        <span>Periksa Laprak</span>
                    </a>
                </div>
                </div>

                {{-- Form Penilaian --}}
                <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
                    <h3 class="text-xs font-black text-gray-800 tracking-widest uppercase mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-4 bg-indigo-600 rounded-full"></span>
                        Review Action ({{ Auth::user()->role }})
                    </h3>

                    @php
                        $userRole = strtoupper(Auth::user()->role);
                        $isAslabAcc = strtoupper($submission->aslab_status) === 'ACC';
                    @endphp

                    @if($userRole === 'DOSEN')
                        <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                            <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest leading-relaxed">
                                Dosen memiliki akses baca untuk laporan mingguan. Approval laporan mingguan dilakukan oleh Aslab dan Laboran.
                            </p>
                        </div>
                    @elseif($userRole === 'LABORAN' && !$isAslabAcc)
                        {{-- Proteksi Laboran --}}
                        <div class="p-6 bg-amber-50 rounded-2xl border border-amber-100 text-center">
                            <svg class="w-8 h-8 text-amber-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <p class="text-[10px] font-bold text-amber-700 uppercase tracking-widest leading-relaxed">
                                Menunggu verifikasi Aslab. Anda baru dapat memeriksa setelah status Aslab "ACC".
                            </p>
                        </div>
                    @else
                        <form action="{{ route('submissions.approve', $submission->id) }}" method="POST" class="space-y-6">
                            @csrf
                            <div>
                                <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3">Catatan / Feedback</label>
                                <textarea name="feedback" rows="5" 
                                    class="block w-full rounded-2xl border-gray-100 text-xs font-bold p-5 bg-gray-50 focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all outline-none resize-none @error('feedback') border-red-500 @enderror"
                                    placeholder="Wajib diisi jika memberikan status REVISI...">{{ old('feedback') }}</textarea>
                                @error('feedback')
                                    <p class="mt-2 text-[9px] font-bold text-red-500 uppercase tracking-tighter">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <button type="submit" name="status" value="ACC" class="w-full py-5 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-2xl shadow-emerald-100 bg-emerald-600 text-white hover:bg-emerald-700 active:scale-[0.98] transition-all">
                                    Berikan ACC
                                </button>
                                <button type="submit" name="status" value="REVISI" class="w-full py-5 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl shadow-red-50 bg-white text-red-600 border-2 border-red-50 hover:bg-red-50 active:scale-[0.98] transition-all">
                                    Minta Revisi
                                </button>
                            </div>
                        </form>
                    @endif
                </div>

                {{-- Riwayat Revisi --}}
                @if($submission->histories && $submission->histories->count() > 0)
                <div class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm">
                    <h4 class="text-[9px] font-black text-gray-400 uppercase mb-6 tracking-widest">History Log ({{ $submission->histories->count() }})</h4>
                    <div class="space-y-4">
                        @foreach($submission->histories as $history)
                        <div class="p-5 bg-gray-50/50 rounded-2xl border border-gray-100 group hover:border-indigo-100 transition-colors">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[9px] font-black text-red-500 uppercase tracking-tighter">Versi #{{ $history->iteration }}</span>
                                <button type="button" 
                                        onclick="compareFilesFullscreen('{{ addslashes($history->drive_link) }}')" 
                                        class="text-[9px] font-black text-indigo-600 uppercase tracking-widest hover:text-indigo-800 flex items-center gap-1">
                                    Compare <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </button>
                            </div>
                            <p class="text-[11px] font-medium text-gray-500 leading-relaxed italic">"{{ $history->feedback ?? 'No notes provided.' }}"</p>
                            <p class="text-[8px] font-black text-gray-300 mt-3 uppercase">{{ $history->created_at->diffForHumans() }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <input type="hidden" id="current_file_link" value="{{ $submission->submission_link }}">

    <script>
        const dynamicContent = document.getElementById('dynamic_content');
        const placeholder = document.getElementById('placeholder_screen');
        const badge = document.getElementById('compare_badge');
        const currentLink = document.getElementById('current_file_link');
        const previewPanel = document.getElementById('preview_panel');
        const previewWrapper = document.getElementById('preview_wrapper');

        function extractId(url) {
            if (!url) return null;
            const match = url.match(/(?:\/d\/|id=|document\/d\/)([\w-]+)/);
            return match ? match[1] : null;
        }

        function handleLivePreview() {
            const fileId = extractId(currentLink.value);
            exitFullscreen();
            placeholder.classList.remove('hidden');

            if (fileId) {
                dynamicContent.innerHTML = `
                    <iframe src="https://drive.google.com/file/d/${fileId}/preview" 
                            class="w-full h-full border-none bg-white rounded-2xl"
                            onload="document.getElementById('placeholder_screen').classList.add('hidden')"></iframe>
                `;
            } else {
                placeholder.classList.add('hidden');
                dynamicContent.innerHTML = `<div class="flex items-center justify-center h-full text-[10px] font-black text-red-400 uppercase">File Link Tidak Valid</div>`;
            }
        }

        function compareFilesFullscreen(historyLink) {
            const historyId = extractId(historyLink);
            const currentId = extractId(currentLink.value);
            if (!historyId) return;

            enterFullscreen();
            dynamicContent.innerHTML = `
                <div class="grid grid-cols-2 gap-4 h-full p-4 bg-slate-900">
                    <div class="flex flex-col h-full">
                        <div class="flex items-center gap-2 mb-3 px-2">
                            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                            <span class="text-[9px] font-black text-red-400 uppercase tracking-[0.2em]">VERSI LAMA (HISTORY)</span>
                        </div>
                        <iframe src="https://drive.google.com/file/d/${historyId}/preview" class="flex-1 w-full border-none bg-white rounded-2xl shadow-2xl"></iframe>
                    </div>
                    <div class="flex flex-col h-full">
                        <div class="flex justify-between items-center mb-3 px-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-indigo-50 rounded-full"></span>
                                <span class="text-[9px] font-black text-indigo-400 uppercase tracking-[0.2em]">FILE TERBARU (CURRENT)</span>
                            </div>
                            <button onclick="handleLivePreview()" class="px-4 py-1.5 bg-slate-800 text-white text-[9px] font-black rounded-lg hover:bg-red-600 transition uppercase tracking-widest">Tutup Perbandingan [X]</button>
                        </div>
                        <iframe src="https://drive.google.com/file/d/${currentId || ''}/preview" class="flex-1 w-full border-none bg-white rounded-2xl shadow-2xl"></iframe>
                    </div>
                </div>
            `;
        }

        function enterFullscreen() {
            previewPanel.classList.add('fixed', 'inset-0', 'z-[100]', 'w-screen', 'h-screen', 'rounded-none');
            previewPanel.classList.remove('lg:w-2/3', 'rounded-[2.5rem]');
            previewWrapper.classList.remove('p-6');
            previewWrapper.classList.add('p-0');
            badge.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function exitFullscreen() {
            previewPanel.classList.remove('fixed', 'inset-0', 'z-[100]', 'w-screen', 'h-screen', 'rounded-none');
            previewPanel.classList.add('lg:w-2/3', 'rounded-[2.5rem]');
            previewWrapper.classList.add('p-6');
            previewWrapper.classList.remove('p-0');
            badge.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        window.onload = handleLivePreview;
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 10px; }
        #dynamic_content { height: 100%; width: 100%; }
    </style>
</x-app-layout>
