<x-guest-layout>
    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">Menunggu verifikasi akun</span>
    <h1 class="mt-5 text-2xl font-bold text-slate-900">Pendaftaran berhasil</h1>
    <p class="mt-3 text-sm leading-7 text-slate-600">Halo {{ auth()->user()->name }}, akun dengan NIM {{ auth()->id() }} sedang menunggu verifikasi Laboran atau Aslab. Tidak perlu verifikasi email. Setelah disetujui, kamu bisa bergabung ke kelas dan mengumpulkan laprak.</p>
    <a href="{{ route('dashboard') }}" class="mt-6 block rounded-xl bg-emerald-700 px-5 py-3 text-center text-sm font-semibold text-white">Periksa status akun</a>
    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">@csrf<button class="text-sm font-semibold text-slate-600">Keluar</button></form>
</x-guest-layout>
