@props(['url' => null])
<div class="rounded-2xl border border-slate-200 bg-white p-5" x-data="{ url: @js($url), previous: null, compare: false, trigger: null, close() { this.compare = false; this.$nextTick(() => this.trigger?.focus()); } }" @document-preview.window="url = $event.detail.url" @document-compare.window="previous = $event.detail.url; trigger = $event.detail.trigger; compare = true; $nextTick(() => $refs.closeCompare.focus())" @keydown.escape.window="if(compare) close()">
    <div class="mb-4 flex items-center justify-between gap-3"><h2 class="font-semibold text-slate-800">Preview dokumen</h2><a x-show="url" :href="url" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-emerald-700">Buka PDF</a></div>
    <p class="mb-4 text-xs leading-6 text-slate-500">Pastikan file PDF di Drive dapat dibaca pemeriksa. Jika preview meminta izin atau tidak tampil, buka dokumen di tab baru dan periksa akses file. Aplikasi tidak menyimpan salinan PDF.</p>
    <template x-if="url"><iframe :src="url" loading="lazy" title="Preview laporan praktikum" class="h-[65vh] min-h-[360px] w-full rounded-xl border border-slate-100" referrerpolicy="no-referrer"></iframe></template>
    <p x-show="!url" class="flex min-h-[360px] items-center justify-center rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada preview. Tempel link file Google Drive yang valid.</p>
    <template x-teleport="body">
        <div x-show="compare" x-cloak role="dialog" aria-modal="true" aria-label="Bandingkan versi dokumen" class="fixed inset-0 z-[100] flex flex-col bg-slate-50 p-4 sm:p-6">
            <div class="mb-4 flex items-center justify-between"><h2 class="font-semibold text-slate-900">Perbandingan dokumen</h2><button x-ref="closeCompare" type="button" @click="close()" class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Tutup perbandingan</button></div>
            <div class="grid min-h-0 flex-1 gap-4 overflow-y-auto md:grid-cols-2">
                <section class="flex min-h-[40vh] flex-col"><h3 class="mb-2 text-sm font-semibold">Versi riwayat</h3><template x-if="compare && previous"><iframe :src="previous" loading="lazy" title="Versi laporan sebelumnya" class="min-h-[35vh] w-full flex-1 rounded-xl border border-slate-200"></iframe></template></section>
                <section class="flex min-h-[40vh] flex-col"><h3 class="mb-2 text-sm font-semibold">Dokumen saat ini / draft baru</h3><template x-if="compare && url"><iframe :src="url" loading="lazy" title="Versi laporan saat ini" class="min-h-[35vh] w-full flex-1 rounded-xl border border-slate-200"></iframe></template><p x-show="!url" class="p-6 text-sm text-slate-500">Isi link dokumen baru untuk membandingkan.</p></section>
            </div>
        </div>
    </template>
</div>
