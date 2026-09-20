<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Sistem Informasi Praktikum untuk pengelolaan kelas, presensi, dan laporan praktikum.">
        <title>{{ config('app.name', 'SI Praktikum') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-950 font-sans text-white antialiased">
        <div class="relative min-h-screen overflow-hidden">
            <div class="absolute inset-0 bg-slate-950"></div>
            <div class="absolute -top-32 -right-24 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-emerald-800/10 blur-3xl"></div>

            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 lg:px-10">
                <header class="flex items-center justify-between py-6">
                    <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="SI Praktikum">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500 shadow-lg shadow-emerald-500/20">
                            <x-application-logo class="h-7 w-7 text-white" />
                        </span>
                        <span class="text-lg font-semibold tracking-tight">SI-<span class="text-emerald-400">Praktikum</span></span>
                    </a>

                    <nav class="flex items-center gap-2" aria-label="Navigasi akun">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-xl bg-white px-5 py-2.5 text-xs font-semibold tracking-normal text-slate-900 transition hover:bg-emerald-50">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-xl px-4 py-2.5 text-xs font-semibold tracking-normal text-slate-300 transition hover:bg-white/10 hover:text-white">
                                Masuk
                            </a>
                            @if(Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-xl bg-white px-5 py-2.5 text-xs font-semibold tracking-normal text-slate-900 transition hover:bg-emerald-50">
                                    Daftar
                                </a>
                            @endif
                        @endauth
                    </nav>
                </header>

                <main class="grid flex-1 items-center gap-14 py-14 lg:grid-cols-[1.1fr_0.9fr] lg:py-20">
                    <section>
                        <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-4 py-2 text-xs font-semibold tracking-normal text-emerald-300">
                            Pengelolaan Praktikum Terpadu
                        </div>
                        <h1 class="max-w-3xl text-4xl font-semibold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                            Praktikum lebih tertata, dari presensi hingga laporan per modul.
                        </h1>
                        <p class="mt-6 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
                            Kelola kelas, pertemuan, presensi, pengumpulan laporan, dan approval berjenjang dalam satu sistem yang mudah digunakan.
                        </p>
                        <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-7 py-4 text-xs font-semibold tracking-normal shadow-xl shadow-emerald-500/20 transition hover:bg-emerald-400">
                                    Buka Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-7 py-4 text-xs font-semibold tracking-normal shadow-xl shadow-emerald-500/20 transition hover:bg-emerald-400">
                                    Mulai Sekarang
                                </a>
                            @endauth
                            <a href="#fitur" class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/5 px-7 py-4 text-xs font-semibold tracking-normal text-slate-200 transition hover:bg-white/10">
                                Lihat Fitur
                            </a>
                        </div>
                    </section>

                    <section id="fitur" class="grid gap-4 sm:grid-cols-2" aria-label="Fitur utama">
                        @foreach([
                            ['Kelas & Semester', 'Kelola kelas aktif dan arsip setiap semester.'],
                            ['Presensi', 'Catat dan rekap kehadiran setiap pertemuan.'],
                            ['Laporan', 'Pantau laporan pada setiap modul praktikum.'],
                            ['Approval', 'Review berjenjang oleh Aslab, Laboran, dan Dosen.'],
                        ] as [$title, $description])
                            <article class="rounded-3xl border border-white/10 bg-white/[0.06] p-6 backdrop-blur-sm transition hover:-translate-y-1 hover:bg-white/[0.09]">
                                <div class="mb-5 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/20 text-sm font-semibold text-emerald-300">
                                    {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </div>
                                <h2 class="font-semibold text-white">{{ $title }}</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-400">{{ $description }}</p>
                            </article>
                        @endforeach
                    </section>
                </main>

                <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-500 sm:text-left">
                    &copy; {{ now()->year }} {{ config('app.name', 'SI Praktikum') }}
                </footer>
            </div>
        </div>
    </body>
</html>
