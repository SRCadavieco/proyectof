@auth
@php
    $hasConnection = auth()->user()->printifyConnection !== null;
    $dismissed     = session('printify_popup_dismissed', false);
@endphp
@if(!$hasConnection && !$dismissed)
<div
    x-data="printifyPopup()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[200] flex items-center justify-center p-4"
    style="display:none"
    @keydown.escape.window="later()"
>
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"
         @click="later()"></div>

    <!-- Card -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="relative z-10 w-full max-w-lg rounded-2xl overflow-hidden"
         style="background:#111;border:1px solid rgba(255,255,255,0.09);">

        <!-- Header band -->
        <div class="px-7 pt-7 pb-5" style="border-bottom:1px solid rgba(255,255,255,0.06)">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background:rgba(124,60,160,0.18);border:1px solid rgba(124,60,160,0.3)">
                        <svg class="w-5 h-5" fill="none" stroke="#c084fc" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-white font-semibold text-base">Connect your Printify account</h2>
                        <p class="text-white/40 text-xs mt-0.5">Push designs directly to your store in one click</p>
                    </div>
                </div>
                <button @click="later()"
                        class="text-white/30 hover:text-white/70 transition-colors mt-0.5 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Steps -->
        <div class="px-7 py-5 space-y-3" style="border-bottom:1px solid rgba(255,255,255,0.06)">
            <p class="text-white/50 text-xs uppercase tracking-widest font-semibold mb-4">How to get your API token</p>

            @foreach([
                ['1', 'Go to <strong>printify.com</strong> and log in to your account.'],
                ['2', 'Click your profile icon (top-right) → <strong>My Profile</strong>.'],
                ['3', 'Scroll down to <strong>Connections</strong> → <strong>API access</strong>.'],
                ['4', 'Click <strong>Generate</strong> (or copy your existing token).'],
                ['5', 'Paste the token in the field below and click <strong>Connect</strong>.'],
            ] as [$num, $text])
            <div class="flex items-start gap-3">
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold flex-shrink-0 mt-px"
                      style="background:rgba(124,60,160,0.25);color:#c084fc">{{ $num }}</span>
                <p class="text-white/55 text-sm leading-relaxed">{!! $text !!}</p>
            </div>
            @endforeach
        </div>

        <!-- Form -->
        <div class="px-7 py-5">
            <form method="POST" action="{{ route('printify.connect') }}" x-ref="form">
                @csrf
                <label class="block text-xs text-white/40 font-medium mb-2 uppercase tracking-wider">Printify API Token</label>
                <div class="flex gap-2">
                    <input type="text"
                           name="api_token"
                           placeholder="pat_xxxxxxxxxxxxxxxxxxxx…"
                           class="flex-1 bg-white/5 border rounded-xl px-4 py-2.5 text-sm text-white placeholder-white/20 focus:outline-none focus:border-purple-500 transition-colors"
                           style="border-color:rgba(255,255,255,0.12)"
                           :disabled="loading"
                           x-ref="tokenInput">
                    <button type="submit"
                            :disabled="loading"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90 flex items-center gap-2 flex-shrink-0"
                            style="background:#7c3ca0">
                        <span x-show="!loading">Connect</span>
                        <span x-show="loading" class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            Connecting…
                        </span>
                    </button>
                </div>
                <p class="text-red-400 text-xs mt-2" x-show="error" x-text="error"></p>
            </form>

            <div class="mt-4 flex items-center justify-between">
                <button @click="later()"
                        class="text-white/30 text-xs hover:text-white/60 transition-colors underline underline-offset-2">
                    Maybe later
                </button>
                <a href="https://printify.com/app/account/api" target="_blank" rel="noopener"
                   class="text-xs flex items-center gap-1 transition-colors"
                   style="color:#9d5bc7">
                    Open Printify API settings
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function printifyPopup() {
    return {
        open: false,
        loading: false,
        error: '',
        init() {
            // Small delay so page renders first
            setTimeout(() => { this.open = true; }, 600);
        },
        later() {
            this.open = false;
            fetch('{{ route('printify.dismiss-popup') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            });
        },
    };
}
</script>
@endif
@endauth
