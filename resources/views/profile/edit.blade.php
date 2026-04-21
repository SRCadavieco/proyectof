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

            {{-- ── Printify connection ── --}}
            <div class="p-4 sm:p-8 bg-white border border-cream-200 sm:rounded-lg">
                <div class="max-w-xl">
                    <h2 class="font-medium text-ink text-base mb-1">Printify</h2>
                    <p class="text-sm text-ink-muted mb-4">Connect your Printify account to send your FabricAI designs directly to your print-on-demand store.</p>

                    @if($errors->has('api_token'))
                        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm">
                            {{ $errors->first('api_token') }}
                        </div>
                    @endif
                    @if(session('printify_error'))
                        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm">
                            {{ session('printify_error') }}
                        </div>
                    @endif
                    @if(session('printify_success'))
                        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm">
                            {{ session('printify_success') }}
                        </div>
                    @endif

                    @php $conn = Auth::user()->printifyConnection; @endphp

                    @if($conn)
                        <div class="flex items-center gap-4 p-4 bg-cream-50 border border-cream-200">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-ink">✓ Connected</p>
                                @if($conn->shop_name)
                                    <p class="text-xs text-ink-muted mt-0.5">Store: <strong>{{ $conn->shop_name }}</strong></p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('printify.disconnect') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-4 py-2 border border-red-300 text-red-600 text-xs font-medium tracking-wide uppercase hover:bg-red-50 transition-colors"
                                        onclick="return confirm('Disconnect your Printify account?')">
                                    Disconnect
                                </button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('printify.connect') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs text-ink-muted mb-1 uppercase tracking-wider">Printify API Token</label>
                                <input type="text" name="api_token" placeholder="Paste your Printify API token here"
                                       class="w-full border border-cream-300 px-3 py-2 text-sm text-ink focus:outline-none focus:border-ink transition-colors"
                                       autocomplete="off">
                            </div>
                            <button type="submit"
                                    class="px-6 py-3 bg-ink text-white text-xs font-medium tracking-widest uppercase hover:bg-purple-900 transition-colors">
                                Connect Printify
                            </button>
                        </form>
                        <p class="mt-3 text-xs text-ink-muted">
                            Get your token at
                            <a href="https://printify.com/app/account#api" target="_blank" rel="noopener noreferrer" class="underline">printify.com → My account → Connections → API</a>.
                            Don't have an account?
                            <a href="https://printify.com/app/register" target="_blank" rel="noopener noreferrer" class="underline">Create one free →</a>
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
