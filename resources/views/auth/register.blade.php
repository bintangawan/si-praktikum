<x-guest-layout>
    <h1 class="mb-3 text-2xl font-bold text-slate-900">Daftar mahasiswa</h1><p class="mb-6 text-sm leading-6 text-slate-500">Setelah mendaftar, tunggu verifikasi akun oleh Laboran atau Aslab. Tidak perlu verifikasi email.</p>
    <form class="space-y-5" method="POST" action="{{ route('register') }}">
        @csrf

        <!-- ID-->
        <div class="mt-4">
            <x-input-label for="id" :value="__('NIM')" />
            <x-text-input id="id" class="block mt-1 w-full" type="text" name="id" :value="old('id')" required />
            <x-input-error :messages="$errors->get('id')" class="mt-2" />
        </div>

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nama lengkap')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Konfirmasi password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Konfirmasi password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500" href="{{ route('login') }}">
                {{ __('Sudah punya akun?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Daftar') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
