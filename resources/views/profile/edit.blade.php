<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-xl text-ink leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white border border-cream-200 sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white border border-cream-200 sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- ── Printful connection ── --}}
            <div class="p-4 sm:p-8 bg-white border border-cream-200 sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="font-medium text-ink text-base mb-1">Printful</h2>
                    <p class="text-sm text-ink-muted mb-4">Connect your Printful account to send your FabricAI designs directly to your print-on-demand store.</p>

                    @if(session('printful_error'))
                        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm">
                            {{ session('printful_error') }}
                        </div>
                    @endif
                    @if(session('printful_success'))
                        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm">
                            {{ session('printful_success') }}
                        </div>
                    @endif

                    @php $conn = Auth::user()->printfulConnection; @endphp

                    @if($conn)
                        <div class="flex items-center gap-4 p-4 bg-cream-50 border border-cream-200">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-ink">✓ Connected</p>
                                @if($conn->store_name)
                                    <p class="text-xs text-ink-muted mt-0.5">Store: <strong>{{ $conn->store_name }}</strong></p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('printful.disconnect') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-4 py-2 border border-red-300 text-red-600 text-xs font-medium tracking-wide uppercase hover:bg-red-50 transition-colors"
                                        onclick="return confirm('Disconnect your Printful account?')">
                                    Disconnect
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('printful.connect') }}"
                           class="inline-block px-6 py-3 bg-ink text-white text-xs font-medium tracking-widest uppercase hover:bg-purple-900 transition-colors">
                            Connect Printful Account
                        </a>
                        <p class="mt-3 text-xs text-ink-muted">
                            Don't have a Printful account?
                            <a href="https://www.printful.com/auth/register" target="_blank" rel="noopener noreferrer" class="underline">Create one free →</a>
                        </p>
                    @endif
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white border border-cream-200 sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
