<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-ink border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-ink-light focus:bg-ink-light active:bg-ink focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
