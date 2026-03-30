@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-ink uppercase tracking-wider']) }}>
    {{ $value ?? $slot }}
</label>
