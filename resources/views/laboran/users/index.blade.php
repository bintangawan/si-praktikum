<x-app-layout>
    <x-slot name="header_title">Manajemen User</x-slot>

    <div class="py-0">
        <div class="max-w-7xl mx-auto space-y-6">
            
            {{-- PANEL FILTER & SEARCH --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <form action="{{ route('users.index') }}" method="GET" class="space-y-6">
                    
                    <div class="flex flex-col md:flex-row gap-4 items-end">
                        {{-- Search Input --}}
                        <div class="flex-1 w-full">
                            <label class="block mb-2 text-xs font-black uppercase tracking-widest text-gray-500">Cari Pengguna</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400 group-focus-within:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <input type="text" name="search" value="{{ request('search') }}" 
                                       class="bg-gray-50 border border-gray-200 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block w-full ps-10 p-3 transition" 
                                       placeholder="Cari ID, Nama, atau Email...">
                            </div>
                        </div>

                        {{-- Limit Select --}}
                        <div class="w-full md:w-32">
                            <label class="block mb-2 text-xs font-black uppercase tracking-widest text-gray-500">Tampilkan</label>
                            <select name="limit" class="bg-gray-50 border border-gray-200 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block w-full p-3 transition">
                                <option value="10" {{ request('limit') == '10' ? 'selected' : '' }}>10</option>
                                <option value="25" {{ request('limit') == '25' ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('limit') == '50' ? 'selected' : '' }}>50</option>
                                <option value="all" {{ request('limit') == 'all' ? 'selected' : '' }}>Semua</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full md:w-auto bg-indigo-600 text-white px-8 py-3 rounded-xl text-sm font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 active:scale-95">
                            Terapkan Filter
                        </button>
                    </div>

                    {{-- CHECKBOX ROLES --}}
                    <div class="pt-4 border-t border-gray-100">
                        <span class="block mb-3 text-xs font-black uppercase tracking-widest text-gray-500">Filter Role:</span>
                        <div class="flex flex-wrap gap-6">
                            @foreach(['Mahasiswa', 'Dosen', 'Laboran', 'Aslab'] as $roleName)
                                <label class="inline-flex items-center cursor-pointer group">
                                    <input type="checkbox" name="roles[]" value="{{ $roleName }}" 
                                           {{ in_array($roleName, $selectedRoles) ? 'checked' : '' }}
                                           class="w-5 h-5 text-indigo-600 bg-gray-50 border-gray-200 rounded-lg focus:ring-indigo-500 transition">
                                    <div class="ms-3 flex flex-col">
                                        <span class="text-sm font-bold text-gray-700 group-hover:text-indigo-600 transition">{{ $roleName }}</span>
                                        @if($roleName === 'Mahasiswa')
                                            <span class="text-[10px] text-gray-400 font-medium leading-none italic">Termasuk Aslab</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>

            {{-- TABEL USER --}}
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 bg-gray-50/50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-5 text-center">Profil</th>
                                <th class="px-6 py-5">ID</th>
                                <th class="px-6 py-5">Nama Pengguna</th>
                                <th class="px-6 py-5 text-center">Status</th>
                                <th class="px-6 py-5 text-center">Aksi Manajemen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($users as $user)
                                <tr class="hover:bg-gray-50/50 transition">
                                    {{-- Avatar --}}
                                    <td class="px-6 py-4">
                                        <div class="flex justify-center">
                                            @if($user->avatar)
                                                <img src="{{ asset('storage/'.$user->avatar) }}" class="w-12 h-12 rounded-2xl object-cover shadow-sm ring-4 ring-white">
                                            @else
                                                <div class="w-12 h-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-700 font-black border border-indigo-100">
                                                    {{ substr($user->name, 0, 1) }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- ID / NIM --}}
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-black text-indigo-600 font-mono tracking-tighter">{{ $user->id }}</span>
                                    </td>

                                    {{-- Nama & Email --}}
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-800 leading-tight">{{ $user->name }}</span>
                                            <span class="text-xs text-gray-400 mt-0.5">{{ $user->email }}</span>
                                        </div>
                                    </td>

                                    {{-- Badge Role --}}
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest
                                            {{ $user->role == 'Mahasiswa' ? 'bg-blue-50 text-blue-600 border border-blue-100' :
                                               ($user->role == 'Dosen' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' :
                                               ($user->role == 'Laboran' ? 'bg-purple-50 text-purple-600 border border-purple-100' : 'bg-orange-50 text-orange-600 border border-orange-100')) }}">
                                            {{ $user->role }}
                                        </span>
                                    </td>

                                    {{-- Tombol Aksi --}}
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            
                                            {{-- Tombol Reset Password --}}
                                            <form action="{{ route('users.reset-password', $user->id) }}" method="POST" onsubmit="return confirm('Reset password {{ $user->name }}?')">
                                                @csrf @method('PATCH')
                                                <button type="submit" title="Reset ke Password Default" class="p-2 text-red-500 hover:bg-red-50 rounded-xl border border-transparent hover:border-red-100 transition">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                                </button>
                                            </form>

                                            {{-- Tombol Kelola Jabatan Aslab --}}
                                            @if(strtoupper($user->role) === 'MAHASISWA')
                                                <form action="{{ route('users.make-aslab', $user->id) }}" method="POST" onsubmit="return confirm('Angkat {{ $user->name }} menjadi Aslab?')">
                                                    @csrf
                                                    <button type="submit" class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition-all border border-indigo-100">
                                                        Angkat Aslab
                                                    </button>
                                                </form>
                                            @elseif(strtoupper($user->role) === 'ASLAB')
                                                <form action="{{ route('users.revoke-aslab', $user->id) }}" method="POST" onsubmit="return confirm('Cabut jabatan Aslab dari {{ $user->name }}?')">
                                                    @csrf
                                                    <button type="submit" class="px-4 py-2 bg-amber-50 text-amber-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-600 hover:text-white transition-all border border-amber-100">
                                                        Jadikan Mhs
                                                    </button>
                                                </form>
                                            @endif

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-20 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="p-4 bg-gray-50 rounded-full mb-4">
                                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                            </div>
                                            <p class="text-gray-400 font-medium italic">Tidak ada data user yang sesuai dengan filter.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINATION --}}
                @if(request('limit') !== 'all' && $users instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="p-6 border-t border-gray-100 bg-gray-50/50">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
