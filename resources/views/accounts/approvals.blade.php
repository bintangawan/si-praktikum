<x-app-layout>
    <x-slot name="header_title">Verifikasi mahasiswa</x-slot>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-600">{{ $pendingCount }} akun menunggu verifikasi. Periksa NIM dan nama sebelum menyetujui.</p>
        <form method="POST" action="{{ route('accounts.approve-all') }}" data-confirm-title="Verifikasi semua akun?" data-confirm="Semua akun mahasiswa yang sedang menunggu, termasuk halaman lainnya, akan langsung diverifikasi." data-confirm-button="Ya, verifikasi semua" data-confirm-color="#047857">@csrf
            <button @disabled($pendingCount === 0) class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white disabled:opacity-40">Verifikasi semua ({{ $pendingCount }})</button>
        </form>
    </div>
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-[680px] w-full text-left text-sm"><thead class="bg-slate-50 text-slate-600"><tr><th class="p-4">NIM</th><th class="p-4">Nama / email</th><th class="p-4">Terdaftar</th><th class="p-4">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">@forelse($students as $student)<tr><td class="p-4">{{ $student->id }}</td><td class="p-4"><p class="font-semibold">{{ $student->name }}</p><p class="text-slate-500">{{ $student->email }}</p></td><td class="p-4">{{ $student->created_at->format('d M Y H:i') }}</td><td class="p-4"><form method="POST" action="{{ route('accounts.approve', $student) }}">@csrf<button class="rounded-lg bg-emerald-50 px-4 py-2 font-semibold text-emerald-800">Verifikasi akun</button></form></td></tr>@empty<tr><td colspan="4" class="p-8 text-center text-slate-500">Tidak ada akun menunggu verifikasi.</td></tr>@endforelse</tbody>
        </table>
    </div>
    <div class="mt-5">{{ $students->links() }}</div>
</x-app-layout>
