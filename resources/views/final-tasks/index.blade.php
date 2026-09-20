<x-app-layout>
    @if(!$finalTask->course->semester->is_active)<p class="mb-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Kelas arsip hanya dapat dibaca.</p>@endif
    <x-slot name="header_title">Kelola Laprak Final: {{ $finalTask->course->course_name ?? $finalTask->course->name }}</x-slot>

    <div class="max-w-[95rem] mx-auto py-8 px-4">
        
        {{-- TOP NAVIGATION & HEADER --}}
        <div class="mb-8 flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            <div>
                <a href="{{ route('courses.show', $finalTask->course) }}"
                   class="inline-flex items-center text-xs font-semibold text-gray-400 tracking-normal hover:text-emerald-600 transition group mb-3">
                    <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Kembali ke Dashboard Course
                </a>
                <div class="flex items-center gap-4">
                    <h2 class="text-3xl font-semibold text-gray-800 tracking-tight leading-none">Kelola Laprak Final</h2>
                    
                    {{-- TOMBOL EDIT DESKRIPSI FINAL TASK --}}
                    @if(in_array(strtoupper(auth()->user()->role), ['ASLAB', 'LABORAN', 'DOSEN']))
                    <button onclick="openEditFinalModal()" class="p-2 bg-emerald-50 text-emerald-600 rounded-xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm border border-emerald-100" title="Edit Deskripsi Tugas Final">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </button>
                    @endif
                </div>
                <p class="text-xs font-bold text-gray-400 mt-2 tracking-normal">{{ $finalTask->course->course_name ?? $finalTask->course->name }}</p>
            </div>
            
            {{-- PANEL PENGATURAN DEADLINE (Tetap di Kanan) --}}
            @if(in_array(strtoupper(auth()->user()->role), ['ASLAB', 'LABORAN', 'DOSEN']))
            <div class="w-full lg:w-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex-shrink-0">
                <form action="{{ route('final-tasks.update-deadline', $finalTask->id) }}" method="POST" class="flex flex-col sm:flex-row items-end gap-3">
                    @csrf
                    <fieldset class="contents" @disabled(!$finalTask->course->semester->is_active)>
                    @method('PUT')
                    <div class="w-full sm:w-auto">
                        <label class="block text-xs font-semibold text-gray-400 tracking-normal mb-2 ml-1">Batas Waktu</label>
                        <input type="datetime-local" name="deadline" 
                               value="{{ $finalTask->deadline ? date('Y-m-d\TH:i', strtotime($finalTask->deadline)) : '' }}" 
                               class="w-full rounded-xl border-gray-200 focus:ring-emerald-600 focus:border-emerald-600 text-xs font-bold text-gray-600 bg-gray-50/50" required>
                    </div>
                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-slate-900 text-white text-xs font-semibold rounded-xl tracking-normal hover:bg-slate-800 transition active:scale-95 shadow-lg shadow-slate-100">
                        Simpan
                    </button>
                </fieldset></form>
            </div>
            @endif
        </div>

        {{-- BARIS 2: DESKRIPSI (LEBAR PENUH SAMPAI UJUNG) --}}
        @if($finalTask->description)
        <div class="mb-8 w-full bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 left-0 w-2 h-full bg-emerald-500"></div>
            <span class="block text-xs font-semibold text-emerald-500 tracking-normal mb-3 ml-2">Deskripsi / Instruksi Tugas Final:</span>
            <p class="text-sm text-gray-600 leading-relaxed italic ml-2">"{{ $finalTask->description }}"</p>
        </div>
        @endif

        {{-- STATS OVERVIEW --}}
        @php
            $totalStudents = $finalTask->course->students->count();
            $submitted = count($submissions);
            $dosenAcc = collect($submissions)->filter(fn($s) => strtoupper($s->dosen_status) === 'ACC')->count();
            $laboranAcc = collect($submissions)->filter(fn($s) => strtoupper($s->laboran_status) === 'ACC')->count();
            $aslabAcc = collect($submissions)->filter(fn($s) => strtoupper($s->aslab_status) === 'ACC')->count();
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm border-l-4 border-l-emerald-500 hover:shadow-md transition">
                <p class="text-xs font-semibold text-gray-400 tracking-normal mb-2">Sudah Kumpul</p>
                <h4 class="text-3xl font-semibold text-emerald-600">{{ $submitted }} <span class="text-lg text-gray-300">/ {{ $totalStudents }}</span></h4>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm border-l-4 border-l-amber-500 hover:shadow-md transition">
                <p class="text-xs font-semibold text-gray-400 tracking-normal mb-2">Verifikasi Aslab</p>
                <h4 class="text-3xl font-semibold text-amber-600">{{ $aslabAcc }} <span class="text-lg text-gray-300">/ {{ $submitted }}</span></h4>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm border-l-4 border-l-emerald-500 hover:shadow-md transition">
                <p class="text-xs font-semibold text-gray-400 tracking-normal mb-2">Verifikasi Laboran</p>
                <h4 class="text-3xl font-semibold text-emerald-600">{{ $laboranAcc }} <span class="text-lg text-gray-300">/ {{ $submitted }}</span></h4>
            </div>
            <div class="bg-white p-6 rounded-[2rem] border border-gray-100 shadow-sm border-l-4 border-l-emerald-700 hover:shadow-md transition">
                <p class="text-xs font-semibold text-gray-400 tracking-normal mb-2">ACC Dosen (FINAL)</p>
                <h4 class="text-3xl font-semibold text-emerald-700">{{ $dosenAcc }} <span class="text-lg text-gray-300">/ {{ $submitted }}</span></h4>
            </div>
        </div>

        {{-- TABEL UTAMA DENGAN FILTER --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
            
            {{-- BAR FILTER PENCARIAN & CHECKBOX --}}
            <div class="p-6 border-b border-gray-50 flex flex-col sm:flex-row justify-between gap-4 items-center bg-gray-50/30">
                <div class="w-full sm:w-1/3 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="searchInput" placeholder="Cari Nama atau NIM..." class="block w-full pl-10 pr-4 py-3 rounded-2xl border-gray-200 text-xs font-bold focus:ring-emerald-50 focus:border-emerald-600 text-gray-600 transition">
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-gray-500 tracking-normal hover:text-emerald-600 transition select-none">
                        <input type="checkbox" id="unsubmittedFilter" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-gray-300 cursor-pointer">
                        Hanya Tampilkan Yang Belum Kumpul
                    </label>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1100px] w-full text-left border-collapse" id="submissionsTable">
                    <thead class="bg-white text-xs font-semibold text-gray-400 border-b border-gray-100">
                        <tr>
                            <th class="px-8 py-4 tracking-normal whitespace-nowrap">Mahasiswa</th>
                            <th class="px-8 py-4 tracking-normal text-center whitespace-nowrap cursor-pointer hover:bg-gray-50 transition group select-none" onclick="toggleSort()">
                                <div class="flex items-center justify-center gap-1">
                                    Waktu Kumpul
                                    <svg id="sortIcon" class="w-3 h-3 text-gray-300 group-hover:text-emerald-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>
                                </div>
                            </th>
                            <th class="px-8 py-4 tracking-normal text-center whitespace-nowrap">Riwayat</th>
                            <th class="px-4 py-4 tracking-normal text-center whitespace-nowrap">
                                <div class="flex flex-col items-center gap-2">
                                    <span>Status Aslab</span>
                                    <select id="aslabFilter" class="text-xs font-bold rounded-lg border-gray-200 py-1 pl-2 pr-6 bg-gray-50 focus:ring-0 text-gray-500 cursor-pointer">
                                        <option value="">Semua Filter</option>
                                        <option value="PENDING">PENDING</option>
                                        <option value="REVISI">REVISI</option>
                                        <option value="DITOLAK">DITOLAK</option>
                                        <option value="ACC">ACC</option>
                                    </select>
                                </div>
                            </th>
                            <th class="px-4 py-4 tracking-normal text-center whitespace-nowrap">
                                <div class="flex flex-col items-center gap-2">
                                    <span>Status Laboran</span>
                                    <select id="laboranFilter" class="text-xs font-bold rounded-lg border-gray-200 py-1 pl-2 pr-6 bg-gray-50 focus:ring-0 text-gray-500 cursor-pointer">
                                        <option value="">Semua Filter</option>
                                        <option value="PENDING">PENDING</option>
                                        <option value="REVISI">REVISI</option>
                                        <option value="DITOLAK">DITOLAK</option>
                                        <option value="ACC">ACC</option>
                                    </select>
                                </div>
                            </th>
                            <th class="px-4 py-4 tracking-normal text-center whitespace-nowrap">
                                <div class="flex flex-col items-center gap-2">
                                    <span>Status Dosen</span>
                                    <select id="dosenFilter" class="text-xs font-bold rounded-lg border-gray-200 py-1 pl-2 pr-6 bg-gray-50 focus:ring-0 text-gray-500 cursor-pointer">
                                        <option value="">Semua Filter</option>
                                        <option value="PENDING">PENDING</option>
                                        <option value="REVISI">REVISI</option>
                                        <option value="DITOLAK">DITOLAK</option>
                                        <option value="ACC">ACC</option>
                                    </select>
                                </div>
                            </th>
                            <th class="px-8 py-6 tracking-normal text-center whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50" id="tableBody">
                        @foreach($finalTask->course->students as $student)
                            @php 
                                $sub = $submissions[$student->id] ?? null; 
                                $subStatus = $sub ? 'true' : 'false';
                                $aslabStat = $sub ? strtoupper($sub->aslab_status) : 'NONE';
                                $laboranStat = $sub ? strtoupper($sub->laboran_status) : 'NONE';
                                $dosenStat = $sub ? strtoupper($sub->dosen_status) : 'NONE';
                                $timestamp = $sub ? \Carbon\Carbon::parse($sub->last_upload_at)->timestamp : 0;
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-all group table-row-item" 
                                data-name="{{ strtolower($student->name) }}" 
                                data-nim="{{ strtolower($student->id) }}"
                                data-submitted="{{ $subStatus }}"
                                data-aslab="{{ $aslabStat }}"
                                data-laboran="{{ $laboranStat }}"
                                data-dosen="{{ $dosenStat }}"
                                data-time="{{ $timestamp }}">
                                
                                <td class="px-8 py-5 whitespace-nowrap">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center overflow-hidden shadow-inner border border-gray-50">
                                            @if($student->avatar)
                                                <img src="{{ asset('storage/' . $student->avatar) }}" alt="{{ $student->name }}" class="w-full h-full object-cover">
                                            @else
                                                <span class="text-slate-400 font-semibold text-xs">{{ strtoupper(substr($student->name, 0, 2)) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="text-gray-800 font-semibold block leading-none mb-1 text-sm">{{ $student->name }}</span>
                                            <span class="text-xs text-gray-400 italic tracking-tighter">{{ $student->id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-center whitespace-nowrap">
                                    @if($sub)
                                        <div class="inline-flex flex-col text-xs font-semibold leading-tight text-center">
                                            <span class="text-gray-700">{{ \Carbon\Carbon::parse($sub->last_upload_at)->timezone('Asia/Jakarta')->format('d M Y') }}</span>
                                            <span class="text-emerald-400 tracking-normal">{{ \Carbon\Carbon::parse($sub->last_upload_at)->timezone('Asia/Jakarta')->format('H:i') }} WIB</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-300 font-semibold tracking-normal italic">Belum Mengumpul</span>
                                    @endif
                                </td>
                                <td class="px-8 py-5 text-center whitespace-nowrap">
                                    @if($sub && $sub->histories_count > 0)
                                        <span class="inline-block whitespace-nowrap px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-semibold shadow-sm border border-emerald-100">
                                            {{ $sub->histories_count }}x Riwayat
                                        </span>
                                    @else
                                        <span class="text-gray-200">—</span>
                                    @endif
                                </td>

                                {{-- STATUS ASLAB + TANGGAL --}}
                                <td class="px-4 py-5 text-center whitespace-nowrap">
                                    @if($sub)
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span class="whitespace-nowrap px-3 py-1.5 rounded-xl text-xs font-semibold border shadow-sm {{ $aslabStat == 'ACC' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : ($aslabStat == 'REVISI' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-amber-50 text-amber-600 border-amber-100') }}">{{ $aslabStat }}</span>
                                            @if($aslabStat === 'ACC' && $sub->aslab_acc_at)
                                                <span class="text-xs font-bold text-gray-400 tracking-normal">{{ \Carbon\Carbon::parse($sub->aslab_acc_at)->format('d/m/Y') }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- STATUS LABORAN + TANGGAL --}}
                                <td class="px-4 py-5 text-center whitespace-nowrap">
                                    @if($sub)
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span class="whitespace-nowrap px-3 py-1.5 rounded-xl text-xs font-semibold border shadow-sm {{ $laboranStat == 'ACC' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : ($laboranStat == 'REVISI' ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-slate-50 text-slate-500 border-slate-100') }}">{{ $laboranStat }}</span>
                                            @if($laboranStat === 'ACC' && $sub->laboran_acc_at)
                                                <span class="text-xs font-bold text-gray-400 tracking-normal">{{ \Carbon\Carbon::parse($sub->laboran_acc_at)->format('d/m/Y') }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- STATUS DOSEN + TANGGAL --}}
                                <td class="px-4 py-5 text-center whitespace-nowrap">
                                    @if($sub)
                                        <div class="flex flex-col items-center gap-1.5">
                                            <span class="whitespace-nowrap px-3 py-1.5 rounded-xl text-xs font-semibold border shadow-sm {{ $dosenStat == 'ACC' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : ($dosenStat == 'REVISI' ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-slate-50 text-slate-500 border-slate-100') }}">{{ $dosenStat }}</span>
                                            @if($dosenStat === 'ACC' && $sub->dosen_acc_at)
                                                <span class="text-xs font-bold text-gray-400 tracking-normal">{{ \Carbon\Carbon::parse($sub->dosen_acc_at)->format('d/m/Y') }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                {{-- KOLOM AKSI DENGAN SYARAT ACC BERJENJANG --}}
                                <td class="px-8 py-5 text-center whitespace-nowrap">
                                    @if($sub)
                                        @php
                                            $isApprovedByAslabAndLaboran = ($aslabStat === 'ACC' && $laboranStat === 'ACC');
                                            $userRole = strtoupper(auth()->user()->role);
                                        @endphp

                                        @if($userRole === 'DOSEN')
                                            @if($isApprovedByAslabAndLaboran)
                                                {{-- Dosen diarahkan ke Handler untuk melakukan ACC Dosen --}}
                                                <a href="{{ route('final-tasks.handler', $sub->id) }}" 
                                                class="whitespace-nowrap inline-block bg-emerald-600 text-white px-6 py-3.5 rounded-2xl text-xs font-semibold hover:bg-emerald-700 shadow-xl active:scale-95 transition-all tracking-normal">
                                                    Review File
                                                </a>
                                            @else
                                                {{-- Kunci tombol jika Aslab / Laboran belum ACC --}}
                                                <div class="inline-flex flex-col items-center gap-1">
                                                    <button type="button" disabled 
                                                            class="whitespace-nowrap inline-block bg-gray-200 text-gray-400 px-6 py-3.5 rounded-2xl text-xs font-semibold cursor-not-allowed border border-gray-300 tracking-normal">
                                                        Menunggu ACC Aslab & Laboran
                                                    </button>
                                                </div>
                                            @endif
                                        @else
                                            {{-- Role ASLAB / LABORAN --}}
                                            <a href="{{ route('final-tasks.handler', $sub->id) }}" 
                                            class="whitespace-nowrap inline-block bg-emerald-600 text-white px-6 py-3.5 rounded-2xl text-xs font-semibold hover:bg-emerald-700 shadow-xl active:scale-95 transition-all tracking-normal">
                                                Review File
                                            </a>
                                        @endif
                                    @else
                                        <span class="whitespace-nowrap text-gray-300 text-xs font-semibold italic tracking-normal opacity-50 block text-center">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div id="emptyState" class="hidden p-16 text-center">
                    <p class="text-xs font-semibold text-gray-400 tracking-normal">Pencarian / Filter tidak menemukan hasil</p>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL EDIT DESKRIPSI FINAL TASK --}}
    @if(in_array(strtoupper(auth()->user()->role), ['ASLAB', 'LABORAN', 'DOSEN']))
    <div id="modalEditFinalTask" class="fixed inset-0 bg-slate-900/60 backdrop-blur-md hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in zoom-in duration-300">
            <form action="{{ route('final-tasks.update-description', $finalTask->id) }}" method="POST">
                @csrf
                    <fieldset class="contents" @disabled(!$finalTask->course->semester->is_active)>
                @method('PUT')
                <div class="p-6 border-b border-gray-50 bg-gray-50/50">
                    <h3 class="text-2xl font-semibold text-gray-800 leading-tight tracking-tight">Edit Deskripsi Final Task</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 tracking-normal mb-2.5">Deskripsi / Instruksi Tugas</label>
                        <textarea name="description" rows="6" class="block w-full rounded-2xl border-gray-100 text-sm font-bold focus:ring-4 focus:ring-emerald-50 p-4 bg-gray-50 transition resize-none" placeholder="Tuliskan instruksi materi...">{{ $finalTask->description }}</textarea>
                    </div>
                </div>
                <div class="p-6 pt-0 flex flex-col gap-4">
                    <button type="submit" class="w-full bg-emerald-600 text-white py-5 rounded-2xl text-xs font-semibold tracking-normal shadow-xl shadow-emerald-100 hover:bg-emerald-700 transition">Update Deskripsi</button>
                    <button type="button" onclick="closeEditFinalModal()" class="w-full text-xs font-semibold text-gray-400 tracking-normal hover:text-red-500 transition">Batalkan</button>
                </div>
            </fieldset></form>
        </div>
    </div>
    @endif

    <script>
        function openEditFinalModal() {
            document.getElementById('modalEditFinalTask').classList.replace('hidden', 'flex');
            document.body.style.overflow = 'hidden';
        }
        function closeEditFinalModal() {
            document.getElementById('modalEditFinalTask').classList.replace('flex', 'hidden');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const unsubmittedFilter = document.getElementById('unsubmittedFilter');
            const aslabFilter = document.getElementById('aslabFilter');
            const laboranFilter = document.getElementById('laboranFilter');
            const dosenFilter = document.getElementById('dosenFilter');
            const tableBody = document.getElementById('tableBody');
            const rows = Array.from(document.querySelectorAll('.table-row-item'));
            const emptyState = document.getElementById('emptyState');
            
            let sortDesc = true; 

            function applyFilters() {
                const query = searchInput.value.toLowerCase();
                const onlyUnsubmitted = unsubmittedFilter.checked;
                const aslab = aslabFilter.value;
                const laboran = laboranFilter.value;
                const dosen = dosenFilter.value;
                
                let visibleCount = 0;

                rows.forEach(row => {
                    const name = row.getAttribute('data-name');
                    const nim = row.getAttribute('data-nim');
                    const submitted = row.getAttribute('data-submitted') === 'true';
                    const aslabStat = row.getAttribute('data-aslab');
                    const laboranStat = row.getAttribute('data-laboran');
                    const dosenStat = row.getAttribute('data-dosen');

                    const matchSearch = name.includes(query) || nim.includes(query);
                    const matchUnsubmitted = !onlyUnsubmitted || (onlyUnsubmitted && !submitted);
                    const matchAslab = aslab === "" || aslabStat === aslab;
                    const matchLaboran = laboran === "" || laboranStat === laboran;
                    const matchDosen = dosen === "" || dosenStat === dosen;

                    if (matchSearch && matchUnsubmitted && matchAslab && matchLaboran && matchDosen) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
            }

            window.toggleSort = function() {
                sortDesc = !sortDesc;
                const icon = document.getElementById('sortIcon');
                icon.style.transform = sortDesc ? 'rotate(0deg)' : 'rotate(180deg)';

                const sortedRows = rows.sort((a, b) => {
                    const timeA = parseInt(a.getAttribute('data-time'));
                    const timeB = parseInt(b.getAttribute('data-time'));
                    return sortDesc ? timeB - timeA : timeA - timeB;
                });

                tableBody.innerHTML = '';
                sortedRows.forEach(row => tableBody.appendChild(row));
            };

            searchInput.addEventListener('input', applyFilters);
            unsubmittedFilter.addEventListener('change', function() {
                if (this.checked) {
                    aslabFilter.value = "";
                    laboranFilter.value = "";
                    dosenFilter.value = "";
                }
                applyFilters();
            });
            aslabFilter.addEventListener('change', applyFilters);
            laboranFilter.addEventListener('change', applyFilters);
            dosenFilter.addEventListener('change', applyFilters);
        });
    </script>
</x-app-layout>
