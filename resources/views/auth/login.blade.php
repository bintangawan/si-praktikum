<x-guest-layout>
    <div class="mb-8">
        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-700 ring-1 ring-inset ring-emerald-600/10">
            Portal Praktikum
        </span>
        <h2 class="mt-4 text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">Selamat datang kembali</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">Masuk menggunakan email, NIM, atau NIP yang telah terdaftar.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ showPassword: false }">
        @csrf

        <div>
            <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Email / NIM / NIP</label>
            <div class="group relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
                <input id="email"
                       class="block w-full rounded-xl border-slate-200 bg-slate-50/70 py-3.5 pl-12 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10"
                       type="text"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="Contoh: 0701231001"
                       required autofocus autocomplete="username">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Password</label>
            <div class="group relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </span>
                <input id="password"
                       class="block w-full rounded-xl border-slate-200 bg-slate-50/70 py-3.5 pl-12 pr-12 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10"
                       :type="showPassword ? 'text' : 'password'"
                       name="password"
                       placeholder="Masukkan password"
                       required autocomplete="current-password">
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition hover:text-emerald-600 focus:outline-none" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.293-3.95m3.249-2.323A9.96 9.96 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.563 2.87M15 12a3 3 0 00-3-3m-7-6l14 18"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex cursor-pointer items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" name="remember">
                <span class="ms-2 text-xs font-medium text-slate-600">Ingat saya</span>
            </label>
            @if (Route::has('password.request'))
                <a class="rounded-md text-xs font-semibold text-emerald-700 transition hover:text-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2" href="{{ route('password.request') }}">
                    Lupa password?
                </a>
            @endif
        </div>

        <button type="submit" class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-700 to-teal-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-700/20 transition hover:-translate-y-0.5 hover:from-emerald-800 hover:to-teal-700 hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-emerald-500/20 active:translate-y-0">
            Masuk ke Sistem
            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>
    </form>
</x-guest-layout>
