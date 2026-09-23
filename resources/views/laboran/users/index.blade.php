<x-app-layout>
    <x-slot name="header_title">Manajemen User</x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        <section class="overflow-hidden rounded-2xl border border-emerald-100 bg-emerald-50/70 px-6 py-5 shadow-sm sm:px-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-emerald-800">Kelola akun dan jabatan pengguna</p>
                    <p class="mt-1 text-sm text-slate-600">Angkat mahasiswa terverifikasi menjadi Aslab atau Laboran, dan atur kembali jabatan Aslab.</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-emerald-800">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    {{ $users->total() }} pengguna
                </span>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <form action="{{ route('users.index') }}" method="GET" class="space-y-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,1fr)_9rem_auto] md:items-end">
                    <div>
                        <label for="user-search" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari Pengguna</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input id="user-search" type="search" name="search" value="{{ request('search') }}" class="block w-full rounded-xl border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500" placeholder="Cari ID, nama, atau email...">
                        </div>
                    </div>
                    <div>
                        <label for="user-limit" class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tampilkan</label>
                        <select id="user-limit" name="limit" class="block w-full rounded-xl border-slate-200 bg-slate-50 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach(['10' => '10', '25' => '25', '50' => '50', '100' => '100'] as $limit => $label)
                                <option value="{{ $limit }}" @selected(request('limit', '10') === $limit)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white shadow-sm shadow-emerald-900/10 transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V19l-4 2v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Terapkan Filter
                    </button>
                </div>

                <fieldset class="border-t border-slate-100 pt-4">
                    <legend class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Filter Role</legend>
                    <div class="flex flex-wrap gap-x-5 gap-y-3">
                        @foreach(['Mahasiswa', 'Dosen', 'Laboran', 'Aslab'] as $roleName)
                            <label class="inline-flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-1 text-sm text-slate-700 transition hover:bg-emerald-50">
                                <input type="checkbox" name="roles[]" value="{{ $roleName }}" @checked(in_array($roleName, $selectedRoles, true)) class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-500">
                                <span>{{ $roleName }}@if($roleName === 'Mahasiswa') <span class="text-xs text-slate-400">(termasuk Aslab)</span>@endif</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Daftar Pengguna</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Atur jabatan melalui menu pilihan pada setiap akun.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-4">Pengguna</th>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4 text-center">Jabatan</th>
                            <th class="px-6 py-4">Kelola Jabatan</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($users as $user)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="px-6 py-4">
                                    <div class="flex min-w-0 items-center gap-3">
                                        @if($user->avatar)
                                            <img src="{{ asset('storage/'.$user->avatar) }}" alt="" class="h-11 w-11 shrink-0 rounded-xl object-cover ring-1 ring-slate-200">
                                        @else
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 font-bold text-emerald-800 ring-1 ring-emerald-100">{{ mb_substr($user->name, 0, 1) }}</div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-800">{{ $user->name }}</p>
                                            <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold tabular-nums text-emerald-800">{{ $user->id }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold
                                        {{ $user->hasRole('Mahasiswa') ? 'border-emerald-100 bg-emerald-50 text-emerald-800' : '' }}
                                        {{ $user->hasRole('Dosen') ? 'border-slate-200 bg-slate-100 text-slate-700' : '' }}
                                        {{ $user->hasRole('Laboran') ? 'border-amber-100 bg-amber-50 text-amber-800' : '' }}
                                        {{ $user->hasRole('Aslab') ? 'border-slate-200 bg-slate-100 text-slate-700' : '' }}">
                                        {{ $user->role }}
                                    </span>
                                    @if($user->hasRole('Mahasiswa') && ! $user->approved_at)
                                        <span class="mt-1 block text-xs text-amber-700">Menunggu verifikasi</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->hasRole('Mahasiswa') && $user->approved_at)
                                        <form action="{{ route('users.update-role', $user) }}" method="POST" class="flex max-w-[24rem] items-center gap-2" data-role-confirm data-user-name="{{ $user->name }}" data-current-role="{{ $user->role }}">
                                            @csrf
                                            <select name="role" required aria-label="Pilih jabatan baru untuk {{ $user->name }}" class="min-w-0 flex-1 rounded-lg border-slate-200 bg-white py-2 text-xs text-slate-700 focus:border-emerald-500 focus:ring-emerald-500">
                                                <option value="" selected disabled>Pilih jabatan baru</option>
                                                <option value="Aslab">Angkat sebagai Aslab</option>
                                                <option value="Laboran">Angkat sebagai Laboran</option>
                                            </select>
                                            <button type="submit" class="shrink-0 rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-800">Terapkan</button>
                                        </form>
                                    @elseif($user->hasRole('Aslab'))
                                        <form action="{{ route('users.update-role', $user) }}" method="POST" class="flex max-w-[24rem] items-center gap-2" data-role-confirm data-user-name="{{ $user->name }}" data-current-role="{{ $user->role }}">
                                            @csrf
                                            <input type="hidden" name="role" value="Mahasiswa">
                                            <span class="text-xs text-slate-500">Kembalikan ke Mahasiswa</span>
                                            <button type="submit" class="ml-auto shrink-0 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 transition hover:bg-amber-100">Cabut Aslab</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Tidak ada perubahan jabatan</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('users.reset-password', $user) }}" method="POST" data-confirm-title="Reset kata sandi?" data-confirm="Kata sandi {{ $user->name }} akan diatur ulang menggunakan ID pengguna." data-confirm-button="Ya, reset" data-confirm-color="#b45309" class="inline-flex">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" aria-label="Reset kata sandi untuk {{ $user->name }}" title="Reset kata sandi" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1 7.778-7.778 5.5 5.5 0 0 1-7.778 7.778ZM11.39 11.61 15.5 15.5m0 0 3 3L22 15l-3-3m-3.5 3.5L19 12"/></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m16 0v-2a4 4 0 00-3-3.87M12 7a4 4 0 11-8 0 4 4 0 018 0zm8 4a4 4 0 10-3.99-4.25"/></svg>
                                    </div>
                                    <p class="mt-3 font-medium text-slate-600">Tidak ada pengguna yang sesuai dengan filter.</p>
                                    <p class="mt-1 text-xs text-slate-400">Coba ubah kata kunci pencarian atau filter jabatan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="border-t border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">{{ $users->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
