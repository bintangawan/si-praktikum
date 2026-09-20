@props(['active', 'href'])

@php
$classes = ($active ?? false)
            ? 'flex items-center px-4 py-2 text-emerald-800 bg-emerald-50 rounded-md shadow-sm font-semibold'
            : 'flex items-center px-4 py-2 text-slate-600 hover:bg-slate-50 hover:text-emerald-800 rounded-md transition-all duration-200';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    <span class="flex items-center text-sm [&>svg]:shrink-0">{{ $slot }}</span>
</a>