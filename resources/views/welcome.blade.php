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
    <body data-locale="{{ app()->getLocale() }}" class="min-h-screen bg-slate-950 font-sans text-white antialiased">
        <div class="relative min-h-screen overflow-hidden">
            <div class="absolute inset-0 bg-slate-950"></div>
            <div class="absolute -top-32 -right-24 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-emerald-800/10 blur-3xl"></div>

            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-4 sm:px-6 lg:px-10">
                <header class="relative z-20 flex items-center justify-between gap-3 py-4 sm:py-6">
                    <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2 sm:gap-3" aria-label="SI Praktikum">
                        <img src="{{ asset('images/logo-uinsu.png') }}" alt="Logo UINSU" class="h-9 w-9 shrink-0 rounded-xl bg-white object-contain p-1 shadow-lg shadow-emerald-500/20 sm:h-11 sm:w-11">
                        <span class="whitespace-nowrap text-sm font-semibold tracking-tight sm:text-lg">SI-<span class="text-emerald-400">Praktikum</span></span>
                    </a>

                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <form action="{{ route('locale.update') }}" method="POST" class="shrink-0">
                            @csrf
                            <label class="sr-only" for="welcome-locale-switch">Pilih bahasa</label>
                            <div class="relative">
                                <select id="welcome-locale-switch" name="locale" onchange="this.form.submit()" class="h-10 w-[4.5rem] appearance-none rounded-xl border border-white/15 bg-white/10 pl-3 pr-8 text-center text-xs font-semibold text-white shadow-sm backdrop-blur transition hover:border-emerald-300/50 focus:border-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-300/30">
                                    <option class="text-slate-900" value="id" @selected(app()->getLocale() === 'id')>ID</option>
                                    <option class="text-slate-900" value="en" @selected(app()->getLocale() === 'en')>EN</option>
                                </select>
                                <svg class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                            </div>
                        </form>

                        <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative">
                            <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="welcome-menu" aria-label="{{ app()->getLocale() === 'en' ? 'Navigation menu' : 'Menu navigasi' }}" class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/15 bg-white/10 text-slate-100 shadow-sm transition hover:border-emerald-300/40 hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-emerald-300/40">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                    <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            <nav id="welcome-menu" x-show="open" x-cloak @click.away="open = false" x-transition.origin.top.right style="display: none" aria-label="{{ app()->getLocale() === 'en' ? 'Account navigation' : 'Navigasi akun' }}" class="absolute right-0 mt-3 w-52 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-white/10 bg-slate-900/95 p-2 shadow-2xl shadow-black/30 backdrop-blur-xl">
                                @auth
                                    <a href="{{ route('dashboard') }}" @click="open = false" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-100 transition hover:bg-white/10 hover:text-emerald-300">Dashboard</a>
                                @else
                                    <a href="{{ route('login') }}" @click="open = false" class="block rounded-xl px-4 py-3 text-sm font-semibold text-slate-100 transition hover:bg-white/10 hover:text-emerald-300">Masuk</a>
                                    @if(Route::has('register'))
                                        <a href="{{ route('register') }}" @click="open = false" class="block rounded-xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-400">Daftar</a>
                                    @endif
                                @endauth
                            </nav>
                        </div>
                    </div>
                </header>

                <main class="grid flex-1 items-center gap-10 py-10 sm:gap-14 sm:py-14 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:py-20">
                    <section class="min-w-0">
                        <div class="mb-5 inline-flex max-w-full items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3.5 py-2 text-xs font-semibold leading-5 text-emerald-300 sm:mb-6 sm:px-4">
                            Pengelolaan Praktikum Terpadu
                        </div>
                        <h1 class="max-w-3xl text-3xl font-semibold leading-[1.15] tracking-tight sm:text-5xl lg:text-6xl">
                            Praktikum lebih tertata, dari presensi hingga laporan per modul.
                        </h1>
                        <p class="mt-5 max-w-2xl text-sm leading-7 text-slate-300 sm:mt-6 sm:text-lg sm:leading-8">
                            Kelola kelas, pertemuan, presensi, pengumpulan laporan, dan approval berjenjang dalam satu sistem yang mudah digunakan.
                        </p>
                        <div class="mt-7 flex flex-col gap-3 sm:mt-9 sm:flex-row sm:flex-wrap">
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-emerald-500 px-7 py-4 text-xs font-semibold shadow-xl shadow-emerald-500/20 transition hover:bg-emerald-400 sm:min-h-14">
                                    Buka Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-emerald-500 px-7 py-4 text-xs font-semibold shadow-xl shadow-emerald-500/20 transition hover:bg-emerald-400 sm:min-h-14">
                                    Mulai Sekarang
                                </a>
                            @endauth
                            <a href="#fitur" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-white/10 bg-white/5 px-7 py-4 text-xs font-semibold text-slate-200 transition hover:bg-white/10 sm:min-h-14">
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

                <footer class="border-t border-white/10 py-5 text-center text-xs text-slate-500 sm:py-6 sm:text-left">
                    &copy; {{ now()->year }} {{ config('app.name', 'SI Praktikum') }}
                </footer>
            </div>
        </div>
    </body>
</html>
