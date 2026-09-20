<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-xl border border-transparent bg-emerald-700 px-5 py-3 text-sm font-semibold text-white tracking-normal transition duration-150 hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
