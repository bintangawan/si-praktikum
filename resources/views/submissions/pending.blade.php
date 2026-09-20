<x-app-layout>
    <x-slot name="header_title">Pengumpulan Menunggu Review</x-slot>

    <div class="py-4">
        <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
            @if($submissions->isEmpty())
                <div class="text-center py-16 px-6">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                    <p class="font-bold text-gray-700">Tidak ada tugas yang perlu direview.</p>
                    <p class="mt-1 text-sm text-gray-400">Semua pengumpulan pada tahap Anda sudah selesai diperiksa.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-[860px] w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 tracking-normal">Mahasiswa</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 tracking-normal">Mata Kuliah</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 tracking-normal">Jenis Tugas</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-400 tracking-normal">Waktu Kirim</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-400 tracking-normal">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($submissions as $submission)
                                @php
                                    $isFinal = (bool) $submission->is_final;
                                    $course = $submission->meeting?->course ?? $submission->finalTask?->course;
                                    $taskLabel = $isFinal
                                        ? 'Laporan Final'
                                        : ($submission->meeting?->title ?? 'Pertemuan tidak tersedia');
                                    $handlerRoute = $isFinal
                                        ? route('final-tasks.handler', $submission)
                                        : route('submissions.handler', $submission);
                                @endphp
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-gray-900">{{ $submission->student->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $submission->student->id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ $course?->course_name ?? 'Kelas tidak tersedia' }}
                                        @if($course)
                                            <span class="text-gray-400">({{ $course->class_group }})</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold tracking-wider {{ $isFinal ? 'bg-slate-100 text-slate-700' : 'bg-emerald-50 text-emerald-700' }}">
                                            {{ $taskLabel }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ ($submission->last_upload_at ?? $submission->created_at)->diffForHumans() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <a href="{{ $handlerRoute }}" class="inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold tracking-normal text-white hover:bg-emerald-700 transition">
                                            Review &amp; ACC
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @if($submissions->hasPages())<div class="p-6">{{ $submissions->links() }}</div>@endif
        </div>
    </div>
</x-app-layout>
