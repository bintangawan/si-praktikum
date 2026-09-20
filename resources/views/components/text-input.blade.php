@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-200 bg-slate-50/70 py-3 text-sm focus:border-emerald-500 focus:ring-emerald-500 rounded-xl shadow-sm']) }}>
