<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <main class="relative min-h-screen min-h-[100dvh] overflow-hidden bg-slate-50">
            <div class="pointer-events-none absolute -left-28 -top-28 h-72 w-72 rounded-full bg-emerald-200/50 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-36 -right-24 h-96 w-96 rounded-full bg-teal-200/40 blur-3xl"></div>

            <div class="relative mx-auto grid min-h-screen min-h-[100dvh] max-w-[1440px] lg:grid-cols-[1.05fr_0.95fr]">
                <section class="relative hidden overflow-hidden bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-800 px-12 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-20 xl:py-16">
                    <div class="pointer-events-none absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,.5) 1px, transparent 0); background-size: 28px 28px;"></div>
                    <div class="pointer-events-none absolute -right-24 top-1/4 h-80 w-80 rounded-full border border-white/10"></div>
                    <div class="pointer-events-none absolute -right-8 top-1/3 h-52 w-52 rounded-full border border-white/10"></div>

                    <a href="{{ url('/') }}" class="relative z-10 inline-flex w-fit items-center gap-4 rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur-sm transition hover:bg-white/15">
                        <span class="flex h-14 w-14 items-center justify-center rounded-xl bg-white p-2 shadow-lg shadow-emerald-950/20">
                            <img src="{{ asset('images/logo-uinsu.png') }}" alt="Logo UIN Sumatera Utara" class="h-full w-full object-contain">
                        </span>
                        <span>
                            <span class="block text-[10px] font-semibold uppercase tracking-[0.24em] text-emerald-200">UIN Sumatera Utara</span>
                            <span class="block text-lg font-bold tracking-tight">SI-Praktikum</span>
                        </span>
                    </a>

                    <div class="relative z-10 max-w-xl py-12">
                        <span class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-4 py-2 text-xs font-semibold text-emerald-100">
                            <span class="h-2 w-2 rounded-full bg-emerald-300 shadow-[0_0_0_5px_rgba(110,231,183,0.12)]"></span>
                            Sistem Informasi Praktikum Terpadu
                        </span>
                        <h1 class="text-4xl font-extrabold leading-tight tracking-tight xl:text-5xl">
                            Praktikum lebih tertata, <span class="text-emerald-300">progres lebih terpantau.</span>
                        </h1>
                        <p class="mt-6 max-w-lg text-base leading-8 text-emerald-100/75">
                            Kelola kelas, presensi, laporan, dan proses verifikasi praktikum dalam satu ruang kerja yang ringkas.
                        </p>

                        <div class="mt-10 grid max-w-lg grid-cols-3 gap-3">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm">
                                <svg class="mb-3 h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <p class="text-xs font-semibold leading-5 text-emerald-50">Jadwal terorganisir</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm">
                                <svg class="mb-3 h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-xs font-semibold leading-5 text-emerald-50">Verifikasi efisien</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur-sm">
                                <svg class="mb-3 h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <p class="text-xs font-semibold leading-5 text-emerald-50">Akses sesuai peran</p>
                            </div>
                        </div>
                    </div>

                    <p class="relative z-10 text-xs text-emerald-200/60">&copy; {{ date('Y') }} Laboratorium UIN Sumatera Utara</p>
                </section>

                <section class="flex min-h-screen min-h-[100dvh] items-center justify-center px-4 py-8 sm:px-8 lg:px-12 xl:px-20">
                    <div class="w-full max-w-md">
                        <a href="{{ url('/') }}" class="mb-7 flex items-center justify-center gap-3 lg:hidden">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white p-2 shadow-lg shadow-emerald-900/10 ring-1 ring-slate-200">
                                <img src="{{ asset('images/logo-uinsu.png') }}" alt="Logo UIN Sumatera Utara" class="h-full w-full object-contain">
                            </span>
                            <span class="text-left">
                                <span class="block text-[9px] font-semibold uppercase tracking-[0.2em] text-emerald-700">UIN Sumatera Utara</span>
                                <span class="block text-lg font-extrabold tracking-tight text-slate-900">SI-Praktikum</span>
                            </span>
                        </a>

                        <div class="rounded-[2rem] border border-white/80 bg-white/90 p-6 shadow-2xl shadow-slate-900/[0.08] backdrop-blur-xl sm:p-9">
                            {{ $slot }}
                        </div>

                        <p class="mt-6 text-center text-xs leading-5 text-slate-400">
                            Butuh bantuan akses? Hubungi pengelola laboratorium.
                        </p>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
