<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SI-PRAKTIKUM') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{--
  1. Tambah variabel `ready: false`
  2. x-init: Saat komponen siap, ubah `ready` jadi true.
     Animasi (transition-all) hanya akan aktif JIKA `ready` bernilai true.
--}}
<body @keydown.escape.window="sidebarOpen = false" class="font-sans antialiased bg-gray-50 text-gray-900"
      data-locale="{{ app()->getLocale() }}"
      x-data="{
        sidebarOpen: window.innerWidth >= 1024 && localStorage.getItem('sidebarState') !== 'false',
        ready: false
      }"
      x-init="
        $watch('sidebarOpen', value => { if (window.innerWidth >= 1024) localStorage.setItem('sidebarState', value); });
        setTimeout(() => ready = true, 50); // Aktifkan transisi sesaat setelah render
      ">

    <div class="flex h-[100dvh] flex-col overflow-hidden">

        {{-- TOP BAR --}}
        <header class="flex-shrink-0 flex items-center justify-between px-4 sm:px-6 py-3 bg-white border-b border-gray-200 shadow-sm z-50">
            <div class="flex items-center gap-4 sm:gap-6">
                <button aria-label="Buka atau tutup navigasi" :aria-expanded="sidebarOpen" @click="sidebarOpen = !sidebarOpen" class="text-gray-500 p-2 hover:bg-gray-100 rounded-lg transition focus:outline-none active:scale-95">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <span class="text-xl font-semibold tracking-tighter text-emerald-900">SI-<span class="text-emerald-500">Praktikum</span></span>
            </div>

            {{-- Bahasa dan menu profil --}}
            <div class="flex flex-shrink-0 items-center gap-2 sm:gap-3">
                <form action="{{ route('locale.update') }}" method="POST" class="shrink-0">
                    @csrf
                    <label class="sr-only" for="locale-switch">Pilih bahasa</label>
                    <select id="locale-switch" name="locale" onchange="this.form.submit()" class="rounded-xl border-slate-200 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="id" @selected(app()->getLocale() === 'id')>ID</option>
                        <option value="en" @selected(app()->getLocale() === 'en')>EN</option>
                    </select>
                </form>
                <div class="relative flex-shrink-0" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center space-x-3 focus:outline-none group p-1.5 hover:bg-gray-50 rounded-full transition max-w-[200px] sm:max-w-[300px]">
                    <div class="text-right hidden sm:block min-w-0 flex-1">
                        <p class="text-sm font-bold text-gray-800 group-hover:text-emerald-600 transition truncate" title="{{ Auth::user()->name }}">
                            {{ Auth::user()->name }}
                        </p>
                        <p class="text-xs text-gray-500 font-medium italic tracking-tighter">{{ Auth::user()->role }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full overflow-hidden border-2 border-white shadow-sm ring-1 ring-gray-200 flex-shrink-0">
                        @if(Auth::user()->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-emerald-100 flex items-center justify-center text-emerald-700 font-bold text-sm">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                        @endif
                    </div>
                </button>

                <div x-show="open" @click.away="open = false"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50"
                     style="display: none;">

                    <div class="px-4 py-2 border-b border-gray-50 mb-1 sm:hidden">
                        <p class="text-xs font-bold text-gray-800 break-words">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-400">{{ Auth::user()->role }}</p>
                    </div>

                    <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Edit Profil
                    </a>
                    <hr class="my-1 border-gray-50">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center w-full text-left px-4 py-2 text-sm text-red-600 font-bold hover:bg-red-50 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Logout
                        </button>
                    </form>
                </div>
                </div>
            </div>
        </header>

        {{-- BOTTOM SECTION --}}
        <div class="flex flex-1 overflow-hidden relative">

            {{-- SIDEBAR: Tambahkan class dinamis `:class="ready ? 'transition-all duration-300' : ''"` --}}
            <aside x-cloak :class="[
                       sidebarOpen ? 'translate-x-0 lg:ml-0' : '-translate-x-full lg:-ml-56',
                       ready ? 'transition-all duration-300' : ''
                   ]"
                   class="absolute inset-y-0 left-0 z-40 w-56 shrink-0 border-r border-slate-200 bg-white text-slate-700 transform lg:static lg:inset-0 shadow-xl flex flex-col">

                <nav @click="if (window.innerWidth < 1024) sidebarOpen = false" class="flex-1 py-6 px-4 space-y-1 overflow-y-auto custom-scrollbar">
                    <x-nav-link-sidebar :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        Dashboard
                    </x-nav-link-sidebar>
                    <x-nav-link-sidebar :href="route('archives.index')" :active="request()->routeIs('archives.index')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h1a2 2 0 012 2v5a2 2 0 01-2 2h-1m-16 0h1a2 2 0 012-2v-5a2 2 0 012-2h1"></path></svg>
                        Arsip Praktikum
                    </x-nav-link-sidebar>
                    <x-nav-link-sidebar :href="route('courses.index')" :active="request()->routeIs('courses.index')">
                        {{-- Ikon Buku Terbuka (Daftar Kelas) --}}
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        Daftar Kelas
                    </x-nav-link-sidebar>
                    <x-nav-link-sidebar :href="route('tutorials.index')" :active="request()->routeIs('tutorials.index')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        Tutorial
                    </x-nav-link-sidebar>

                    <div class="my-4 border-t border-emerald-800/50"></div>

                    @if(auth()->user()->hasRole('Laboran', 'Aslab'))
                        <x-nav-link-sidebar :href="route('accounts.approvals')" :active="request()->routeIs('accounts.*')">Verifikasi mahasiswa</x-nav-link-sidebar>
                    @endif
                    @if(auth()->user()->role === 'Laboran')
                        <p class="px-4 text-xs font-bold text-emerald-400 tracking-normal mb-2">Laboran</p>
                        <x-nav-link-sidebar :href="route('users.index')" :active="request()->routeIs('users.index')">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Manajemen User
                        </x-nav-link-sidebar>
                        <x-nav-link-sidebar :href="route('user.import.form')" :active="request()->routeIs('user.import.form')">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            Import User
                        </x-nav-link-sidebar>
                        <x-nav-link-sidebar :href="route('courses.create')" :active="request()->routeIs('courses.create')">
                            {{-- Ikon Folder Plus (Buat Kelas Baru) --}}
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                            Buat Kelas
                        </x-nav-link-sidebar>
                        <x-nav-link-sidebar :href="route('semesters.index')" :active="request()->routeIs('semesters.index')">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Semester Aktif
                        </x-nav-link-sidebar>
                        <x-nav-link-sidebar :href="route('submissions.pending')" :active="request()->routeIs('submissions.pending')">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Periksa Laprak
                        </x-nav-link-sidebar>

                    @elseif(auth()->user()->role === 'Dosen')
                        <p class="px-4 text-xs font-bold text-emerald-400 tracking-normal mb-2">Dosen</p>
                        <x-nav-link-sidebar :href="route('submissions.pending')" :active="request()->routeIs('submissions.pending')">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Periksa Laprak
                        </x-nav-link-sidebar>

                    @elseif(auth()->user()->role === 'Aslab')
                        <p class="px-4 text-xs font-bold text-emerald-400 tracking-normal mb-2">Aslab</p>
                        <x-nav-link-sidebar :href="route('submissions.pending')" :active="request()->routeIs('submissions.pending')">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Periksa Laprak
                        </x-nav-link-sidebar>

                    @elseif(auth()->user()->role === 'Mahasiswa')
                        <p class="px-4 text-xs font-bold text-emerald-400 tracking-normal mb-2">Mahasiswa</p>
                        <x-nav-link-sidebar :href="route('submissions.my-index')" :active="request()->routeIs('submissions.my-index')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Tugas Saya
                        </x-nav-link-sidebar>
                    @endif
                </nav>
            </aside>

            <main class="relative w-full min-w-0 flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/50 p-4 sm:p-5 md:p-6">
                <div class="max-w-7xl mx-auto">
                    @if(isset($header_title) || isset($header))
                        <div class="mb-8 border-b border-gray-200 pb-5">
                            @isset($header_title)
                                <h1 class="text-2xl md:text-3xl font-semibold text-gray-900 tracking-tight">
                                    {{ $header_title }}
                                </h1>
                            @else
                                <div class="text-gray-900">{{ $header }}</div>
                            @endisset
                        </div>
                    @endif

                    @php
                        $flashType = session('error') ? 'error' : (session('success') ? 'success' : (session('info') ? 'info' : null));
                        $flashMessage = session('error') ?? session('success') ?? session('info');
                    @endphp
                    @if($flashMessage)
                        <div id="app-flash-message" hidden data-type="{{ $flashType }}" data-message="{{ $flashMessage }}"></div>
                    @endif
                    @if(isset($course) && $course->isArchived())<p class="mb-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Kelas arsip hanya dapat dibaca.</p>@endif
                    {{ $slot }}
                </div>
            </main>

            <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition:opacity
                 class="absolute inset-0 z-30 bg-gray-900/60 backdrop-blur-sm lg:hidden"
                 style="display: none;">
            </div>
        </div>
    </div>
</body>
</html>
