<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import Data User') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold">Upload File Excel</h3>
                    </div>
                    <hr class="mb-6">

                    <form id="importForm" action="{{ route('user.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700 mb-2">Pilih File (.xlsx / .xls)</label>
                            <input type="file" name="file" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border rounded-lg p-2">
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Mulai Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Daftar Berhasil --}}
                @if (session('import_success'))
                    <div class="overflow-hidden border-t-4 border-emerald-500 bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h6 class="mb-3 flex items-center font-bold text-emerald-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Berhasil Didaftarkan
                            </h6>
                            <div class="max-h-64 overflow-y-auto">
                                <ul class="space-y-1 text-sm text-gray-600">
                                    @foreach (session('import_success') as $msg)
                                        <li class="rounded bg-emerald-50 p-2 italic">{{ $msg }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Daftar Gagal --}}
                @if (session('import_fails'))
                    <div class="overflow-hidden border-t-4 border-red-500 bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h6 class="mb-3 flex items-center font-bold text-red-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Gagal / Duplikat
                            </h6>
                            <div class="max-h-64 overflow-y-auto">
                                <ul class="space-y-1 text-sm text-gray-600">
                                    @foreach (session('import_fails') as $msg)
                                        <li class="rounded bg-red-50 p-2 italic">{{ $msg }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="loading-overlay" class="fixed inset-0 bg-white/80 z-[9999] hidden items-center justify-center flex-col">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
        <p class="mt-4 font-bold text-emerald-600">Memproses Database Mahasiswa...</p>
    </div>

    <script>
        document.getElementById('importForm').addEventListener('submit', function() {
            document.getElementById('loading-overlay').style.display = 'flex';
        });
    </script>
</x-app-layout>
