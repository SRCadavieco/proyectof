@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-cream-300 bg-white text-ink focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-sm']) }}>
