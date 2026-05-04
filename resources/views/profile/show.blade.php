<x-app-layout>
    <x-slot name="title">My Profile</x-slot>
    <x-slot name="bodyClass">bg-[#0d0d0d]</x-slot>
    <x-slot name="navDarkHero">true</x-slot>

    {{-- ── Hero banner ── --}}
    <div class="bg-ink">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-10 flex flex-col sm:flex-row items-start sm:items-center gap-6">
            {{-- Avatar --}}
            @if($user->avatar)
                <img src="{{ $user->avatar }}" alt="{{ $user->name }}"
                     class="w-20 h-20 rounded-full object-cover border-2 border-white/20 shadow-lg shrink-0">
            @else
                <div class="w-20 h-20 rounded-full border-2 border-white/20 bg-white/10 flex items-center justify-center text-white text-3xl font-bold select-none shrink-0">
                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
            @endif

            <div class="flex-1 min-w-0">
                <h1 class="font-serif text-3xl text-white">{{ $user->name }}</h1>
                <p class="text-white/50 text-sm mt-1 truncate">{{ $user->email }}</p>
                <div class="flex flex-wrap items-center gap-3 mt-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-widest
                        {{ in_array(strtolower($user->plan ?? 'free'), ['pro', 'studio']) ? 'bg-purple-500/30 text-purple-200' : 'bg-white/10 text-white/60' }}">
                        {{ ucfirst($user->plan ?? 'Free') }}
                    </span>
                    <span class="inline-flex items-center gap-1 text-white/40 text-xs">
                        <img src="/images/spool.webp" class="w-3.5 h-3.5 object-contain opacity-60" alt="">
                        {{ number_format($user->tokens ?? 0) }} Spools remaining
                    </span>
                    <a href="{{ route('profile.edit') }}"
                       class="inline-flex items-center gap-1.5 text-xs text-white/50 hover:text-white transition-colors">
                        <i class="fas fa-pen text-[10px]"></i> Edit profile
                    </a>
                </div>
            </div>
        </div>

        {{-- Stats row --}}
        <div class="max-w-5xl mx-auto px-4 sm:px-6 pb-8">
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                    <p class="text-2xl font-serif font-bold text-white">{{ number_format($stats['images_generated']) }}</p>
                    <p class="text-[11px] text-white/40 mt-0.5 uppercase tracking-wider">Designs created</p>
                </div>
                <div class="bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                    <p class="text-2xl font-serif font-bold text-white">{{ number_format($stats['tokens_used']) }}</p>
                    <p class="text-[11px] text-white/40 mt-0.5 uppercase tracking-wider">Spools used</p>
                </div>
                <div class="bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                    <p class="text-2xl font-serif font-bold text-white">{{ number_format($stats['products_pushed']) }}</p>
                    <p class="text-[11px] text-white/40 mt-0.5 uppercase tracking-wider">Products pushed</p>
                </div>
            </div>
        </div>
    </div>

    <div class="py-8" x-data="{ showPrintifyModal: false }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('subscription_cancelled'))
                <div class="px-4 py-3 rounded-lg flex items-center gap-2 text-sm" style="background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.25);color:#fde68a">
                    <i class="fas fa-circle-info shrink-0"></i>
                    {{ session('subscription_cancelled') }}
                </div>
            @endif

            {{-- ── Printify Integration Card ── --}}
            <div class="sm:rounded-xl p-6 sm:p-8" style="background:#111;border:1px solid rgba(255,255,255,0.08)">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-medium text-white text-base flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-full bg-[#FF4D00]"></span>
                            Printify Integration
                        </h3>
                        @if($printify)
                            <p class="text-sm text-green-400 mt-1">
                                <i class="fas fa-circle-check mr-1"></i>
                                Connected
                                @if($printify->shop_name)
                                    to <span class="font-semibold">{{ $printify->shop_name }}</span>
                                @endif
                            </p>
                        @else
                            <p class="text-sm text-white/50 mt-1">
                                Connect your Printify account to send designs directly to your print-on-demand store.
                            </p>
                        @endif
                    </div>

                    <div class="shrink-0 flex gap-3">
                        <button @click="showPrintifyModal = true"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded text-white text-sm font-semibold
                                       bg-[#FF4D00] hover:bg-[#e04400] active:bg-[#c93d00] transition-colors shadow-sm">
                            <i class="fas fa-plug"></i>
                            {{ $printify ? 'Reconnect to Printify' : 'Connect to Printify' }}
                        </button>

                        @if($printify)
                            <form method="POST" action="{{ route('printify.disconnect') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded text-sm font-medium
                                               text-red-400 transition-colors" style="border:1px solid rgba(239,68,68,0.3)" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'">
                                    <i class="fas fa-unlink"></i> Disconnect
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if(session('printify_success'))
                    <div class="mt-4 px-3 py-2.5 rounded-lg text-green-400 text-sm" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.2)">
                        <i class="fas fa-circle-check mr-1"></i>{{ session('printify_success') }}
                    </div>
                @endif
                @if($errors->has('api_token'))
                    <div class="mt-4 px-3 py-2.5 rounded-lg text-red-400 text-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2)">
                        <i class="fas fa-circle-exclamation mr-1"></i>{{ $errors->first('api_token') }}
                    </div>
                @endif
            </div>

            {{-- ── Plan ── --}}
            @php
                $plan = strtolower($user->plan ?? 'free');
                $planMeta = [
                    'free'     => ['label' => 'Free',     'price' => '$0',  'accent' => '#444',   'perks' => ['5 Spools to start', 'All AI models', 'Printify integration'], 'desc' => 'Perfect for exploring FabricAI.'],
                    'starter'  => ['label' => 'Starter',  'price' => '$7',  'accent' => '#7c3ca0','perks' => ['Up to 80 Spools/month', 'Daily refill', 'All AI models', 'Printify integration', 'Choose 1 garment color per upload'], 'desc' => 'For creators getting serious.'],
                    'pro'      => ['label' => 'Pro',      'price' => '$12', 'accent' => '#c084fc','perks' => ['Up to 200 Spools/month', 'All AI models', 'Printify integration', 'Upload in all colors', 'Back placement'], 'desc' => 'For freelancers who design regularly.'],
                    'business' => ['label' => 'Business', 'price' => '$25', 'accent' => '#a855f7','perks' => ['Up to 500 Spools/month', 'All AI models', 'Printify integration', 'Upload in all colors', 'Back placement'], 'desc' => 'For studios with high-volume needs.'],
                ];
                $meta = $planMeta[$plan] ?? $planMeta['free'];
            @endphp

            <div class="sm:rounded-xl overflow-hidden flex flex-col" style="background:#111;border:1px solid rgba(255,255,255,0.08)">

                {{-- Card header --}}
                <div class="relative px-6 pt-6 pb-6" style="background:#1a1a1a;border-bottom:1px solid rgba(255,255,255,0.07)">
                    <div class="absolute top-0 left-0 right-0 h-0.5" style="background:{{ $meta['accent'] }}"></div>
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-widest text-white mb-2" style="background:{{ $meta['accent'] }}20;border:1px solid {{ $meta['accent'] }}50">
                                {{ $meta['label'] }}
                            </span>
                            <p class="text-[11px] text-white/40 uppercase tracking-wide font-medium">Current plan</p>
                        </div>
                        <div class="text-right">
                            <span class="font-serif text-4xl font-bold text-white">{{ $meta['price'] }}</span>
                            <span class="text-xs text-white/40 ml-1">/ month</span>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-white/50">{{ $meta['desc'] }}</p>
                </div>

                {{-- Features list --}}
                <div class="flex-1 px-6 py-5">
                    <ul class="space-y-2.5">
                        @foreach($meta['perks'] as $perk)
                            <li class="flex items-center gap-2.5 text-sm text-white/70">
                                <span class="w-1 h-1 rounded-full shrink-0" style="background:{{ $meta['accent'] }}"></span>
                                {{ $perk }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- CTA --}}
                <div class="px-6 py-4" style="border-top:1px solid rgba(255,255,255,0.07)">
                    @if(in_array($plan, ['pro', 'business', 'studio']))
                        <div class="flex flex-col gap-2">
                            <a href="/pricing"
                               class="inline-flex w-full items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-colors"
                               style="background:#7c3ca0" onmouseover="this.style.background='#5a2275'" onmouseout="this.style.background='#7c3ca0'">
                                <i class="fas fa-repeat text-xs"></i> Change plan
                            </a>
                            <form method="POST" action="{{ route('subscription.cancel.confirm') }}"
                                  x-data
                                  @submit.prevent="if(confirm('Cancel subscription? You will be moved to the Free plan immediately.')) $el.submit()">
                                @csrf
                                <button type="submit"
                                        class="inline-flex w-full items-center justify-center gap-2 px-5 py-2 rounded-xl text-red-400 text-sm font-medium transition-colors"
                                        style="border:1px solid rgba(239,68,68,0.3)" onmouseover="this.style.background='rgba(239,68,68,0.1)'" onmouseout="this.style.background='transparent'">
                                    <i class="fas fa-circle-xmark text-xs"></i> Cancel subscription
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="/pricing"
                           class="inline-flex w-full items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold transition-colors"
                           style="background:#7c3ca0" onmouseover="this.style.background='#5a2275'" onmouseout="this.style.background='#7c3ca0'">
                            <i class="fas fa-arrow-up text-xs"></i> Upgrade plan
                        </a>
                    @endif
                </div>

            </div>

        </div>

        {{-- ════════════════════════════════════════
             PRINTIFY CONNECT MODAL
        ════════════════════════════════════════ --}}
        <div x-show="showPrintifyModal"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                 @click="showPrintifyModal = false"></div>

            <div class="relative rounded-xl shadow-2xl max-w-lg w-full p-6 sm:p-8 z-10" style="background:#1a1a1a;border:1px solid rgba(255,255,255,0.1)"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-2">

                {{-- Header --}}
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-[#FF4D00]/10">
                            <i class="fas fa-plug text-[#FF4D00]"></i>
                        </span>
                        <h2 class="font-serif text-lg text-white">Connect to Printify</h2>
                    </div>
                    <button @click="showPrintifyModal = false"
                            class="text-white/40 hover:text-white transition-colors p-1 rounded focus:outline-none">
                        <i class="fas fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Tutorial --}}
                <div class="rounded-lg p-4 mb-5 text-sm space-y-2" style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.5)">
                    <p class="font-semibold text-white text-sm mb-2 flex items-center gap-1.5">
                        <i class="fas fa-circle-info text-[#FF4D00]"></i>
                        How to get your Printify API Token
                    </p>
                    <ol class="list-decimal list-inside space-y-1.5 pl-1">
                        <li>Log in to your <span class="font-medium text-white">Printify</span> account.</li>
                        <li>Go to <span class="font-medium text-white">My account</span> (top-right corner).</li>
                        <li>Select <span class="font-medium text-white">Connections</span> in the left sidebar.</li>
                        <li>Under <span class="font-medium text-white">API access tokens</span>, click <span class="font-medium text-white">Generate new token</span>.</li>
                        <li>Give it a name (e.g. "FabricAI") and <span class="font-medium text-white">copy it</span> before closing.</li>
                    </ol>
                    <p class="pt-1">
                        <a href="https://printify.com/app/account/api" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 text-[#FF4D00] hover:underline font-medium">
                            Go to Printify API Settings
                            <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                        </a>
                    </p>
                </div>

                {{-- Form --}}
                <form method="POST" action="{{ route('printify.connect') }}">
                    @csrf

                    <label for="api_token" class="block text-sm font-medium text-white/80 mb-1.5">
                        Your Printify API Token
                    </label>
                    <input type="text"
                           id="api_token"
                           name="api_token"
                           placeholder="pst-xxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                           autocomplete="off"
                           class="w-full rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none transition"
                           style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.15);" onfocus="this.style.borderColor='rgba(255,77,0,0.6)'" onblur="this.style.borderColor='rgba(255,255,255,0.15)'"
                           value="{{ old('api_token') }}">

                    <div class="flex justify-end gap-3 mt-5">
                        <button type="button"
                                @click="showPrintifyModal = false"
                                class="px-5 py-2 text-sm font-medium text-white/50 hover:text-white rounded-lg transition-colors"
                                style="border:1px solid rgba(255,255,255,0.12)" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-6 py-2 text-sm font-semibold text-white rounded-lg
                                       bg-[#FF4D00] hover:bg-[#e04400] active:bg-[#c93d00]
                                       transition-colors shadow-sm">
                            Save connection
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

    @if($errors->has('api_token'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.querySelector('[x-data]');
                if (el && el._x_dataStack) {
                    el._x_dataStack[0].showPrintifyModal = true;
                }
            });
        </script>
    @endif
</x-app-layout>
