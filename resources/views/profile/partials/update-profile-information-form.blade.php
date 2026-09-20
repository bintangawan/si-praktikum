<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Profile Information') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __("Update your account's profile information and avatar.") }}</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    {{-- PENTING: Tambahkan enctype="multipart/form-data" --}}
    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        {{-- BAGIAN FOTO/AVATAR --}}
        <div>
            <x-input-label for="avatar" :value="__('Photo Profile')" />
            
            @if($user->avatar)
                <div class="mt-2 mb-4">
                    <img src="{{ asset('storage/' . $user->avatar) }}" 
                         alt="Avatar" 
                         class="h-20 w-20 rounded-full object-cover border-2 border-emerald-500 shadow-sm">
                </div>
            @else
                <div class="mt-2 mb-4">
                    <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                        <span class="text-xs">No Photo</span>
                    </div>
                </div>
            @endif

            <input id="avatar" name="avatar" type="file" 
                class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" 
                accept="image/*">
            
            <p class="mt-1 text-xs text-gray-500 tracking-wider">Maksimal 1 MB (JPG, PNG, WEBP)</p>
            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>

        {{-- Input Name --}}
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        {{-- Input Email --}}
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
            {{-- Bagian email verification (tetap seperti kode Anda sebelumnya) --}}
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>