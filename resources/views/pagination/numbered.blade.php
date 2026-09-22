@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        if ($lastPage <= 5) {
            $mobilePages = range(1, $lastPage);
        } elseif ($currentPage <= 3) {
            $mobilePages = [1, 2, 3, null, $lastPage];
        } elseif ($currentPage >= $lastPage - 2) {
            $mobilePages = [1, null, $lastPage - 2, $lastPage - 1, $lastPage];
        } else {
            $mobilePages = [1, null, $currentPage, null, $lastPage];
        }
    @endphp

    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
        <p class="text-center text-sm text-gray-600 sm:text-left">
            Menampilkan <span class="font-semibold">{{ $paginator->firstItem() }}</span>&ndash;<span class="font-semibold">{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold">{{ $paginator->total() }}</span> data
        </p>

        <div class="w-full max-w-full overflow-x-auto sm:hidden">
            <div class="flex min-w-max items-center justify-center gap-1 py-1">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Halaman sebelumnya" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400">
                        @include('pagination.chevron', ['direction' => 'left'])
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">
                        @include('pagination.chevron', ['direction' => 'left'])
                    </a>
                @endif

                @foreach ($mobilePages as $page)
                    @if ($page === null)
                        <span aria-hidden="true" class="inline-flex h-9 w-3 shrink-0 items-center justify-center text-sm text-gray-500">&hellip;</span>
                    @elseif ($page === $currentPage)
                        <span aria-current="page" aria-label="Halaman {{ $page }}" class="inline-flex h-9 min-w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-600 px-1 text-sm font-semibold text-white">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" aria-label="Ke halaman {{ $page }}" class="inline-flex h-9 min-w-8 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-1 text-sm font-medium text-gray-700 hover:bg-gray-100">{{ $page }}</a>
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">
                        @include('pagination.chevron', ['direction' => 'right'])
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Halaman berikutnya" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400">
                        @include('pagination.chevron', ['direction' => 'right'])
                    </span>
                @endif
            </div>
        </div>

        <div class="hidden sm:flex sm:flex-wrap sm:items-center sm:justify-end sm:gap-1.5">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Halaman sebelumnya" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400">@include('pagination.chevron', ['direction' => 'left'])</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">@include('pagination.chevron', ['direction' => 'left'])</a>
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
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-100">@include('pagination.chevron', ['direction' => 'right'])</a>
            @else
                <span aria-disabled="true" aria-label="Halaman berikutnya" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 text-gray-400">@include('pagination.chevron', ['direction' => 'right'])</span>
            @endif
        </div>
    </nav>
@endif
