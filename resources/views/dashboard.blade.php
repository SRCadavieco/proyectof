<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white border border-cream-200 overflow-hidden sm:rounded-lg">
                <div class="p-6 text-ink">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
