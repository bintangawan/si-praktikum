{{-- Kita asumsikan Anda menggunakan layout bawaan Laravel Breeze atau layout buatan sendiri --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard ') . auth()->user()->role }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            {{-- LOGIKA PEMANGGILAN PARTIAL VIEW --}}
            @if(auth()->user()->role === 'Dosen')
                @include('dashboard._dosen')
            @elseif(auth()->user()->role === 'Mahasiswa')
                @include('dashboard._mahasiswa')
            @elseif(auth()->user()->role === 'Aslab')
                @include('dashboard._aslab')
            @elseif(auth()->user()->role === 'Laboran')
                @include('dashboard._laboran')
            @endif

        </div>
    </div>
</x-app-layout>
