@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-600">
            Menampilkan <span class="font-semibold">{{ $paginator->firstItem() }}</span>–<span class="font-semibold">{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold">{{ $paginator->total() }}</span> data
        </p>

        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            $mobileStart = max(1, min($currentPage - 1, $lastPage - 2));
            $mobileEnd = min($lastPage, $mobileStart + 2);
        @endphp

        <div class="flex items-center gap-1.5 sm:hidden">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Halaman sebelumnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400">
                    <span aria-hidden="true">&lsaquo;</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">
                    <span aria-hidden="true">&lsaquo;</span>
                </a>
            @endif

            @for ($page = $mobileStart; $page <= $mobileEnd; $page++)
                @if ($page === $currentPage)
                    <span aria-current="page" aria-label="Halaman {{ $page }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-emerald-600 px-2 text-sm font-semibold text-white">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" aria-label="Ke halaman {{ $page }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-2 text-sm font-medium text-gray-700 hover:bg-gray-100">{{ $page }}</a>
                @endif
            @endfor

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">
                    <span aria-hidden="true">&rsaquo;</span>
                </a>
            @else
                <span aria-disabled="true" aria-label="Halaman berikutnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400">
                    <span aria-hidden="true">&rsaquo;</span>
                </span>
            @endif
        </div>

        <div class="hidden sm:flex sm:items-center sm:gap-1.5">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Halaman sebelumnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400">&lsaquo;</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">&lsaquo;</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-hidden="true" class="inline-flex h-9 min-w-9 items-center justify-center px-1 text-sm text-gray-500">{{ $element }}</span>
                @else
                    @foreach ($element as $page => $url)
                        @if ($page == $currentPage)
                            <span aria-current="page" aria-label="Halaman {{ $page }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-emerald-600 px-2 text-sm font-semibold text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Ke halaman {{ $page }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-2 text-sm font-medium text-gray-700 hover:bg-gray-100">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">&rsaquo;</a>
            @else
                <span aria-disabled="true" aria-label="Halaman berikutnya" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400">&rsaquo;</span>
            @endif
        </div>
    </nav>
@endif
