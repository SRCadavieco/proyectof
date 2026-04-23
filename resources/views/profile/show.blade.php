<x-app-layout>
    <x-slot name="header">
        <h2 class="font-serif text-xl text-ink leading-tight">
            My Profile
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ showPrintifyModal: false }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('subscription_cancelled'))
                <div class="px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg flex items-center gap-2">
                    <i class="fas fa-circle-info shrink-0"></i>
                    {{ session('subscription_cancelled') }}
                </div>
            @endif

            {{-- ── Profile Card ── --}}
            <div class="bg-white border border-cream-200 sm:rounded-lg p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">

                    {{-- Left: name & email --}}
                    <div class="flex-1">
                        <h3 class="font-serif text-2xl text-ink">{{ $user->name }}</h3>
                        <p class="text-sm text-ink-muted mt-1">{{ $user->email }}</p>
                        <p class="text-xs text-ink-muted mt-1">
                            Plan: <span class="font-semibold capitalize">{{ $user->plan ?? 'Free' }}</span>
                            &nbsp;·&nbsp;
                            Tokens remaining: <span class="font-semibold">{{ $user->tokens ?? 0 }}</span>
                        </p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <a href="{{ route('profile.edit') }}"
                               class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-muted border border-cream-300 px-4 py-2 rounded hover:bg-cream-100 transition-colors">
                                <i class="fas fa-pen-to-square"></i> Edit Profile
                            </a>
                        </div>
                    </div>

                    {{-- Right: avatar --}}
                    <div class="shrink-0">
                        @if($user->avatar)
                            <img src="{{ $user->avatar }}"
                                 alt="{{ $user->name }}"
                                 class="w-24 h-24 rounded-full object-cover border-2 border-cream-300 shadow-sm">
                        @else
                            <div class="w-24 h-24 rounded-full bg-ink flex items-center justify-center text-white text-3xl font-bold select-none shadow-sm">
                                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ── Printify Integration Card ── --}}
            <div class="bg-white border border-cream-200 sm:rounded-lg p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-medium text-ink text-base flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-full bg-[#FF4D00]"></span>
                            Printify Integration
                        </h3>
                        @if($printify)
                            <p class="text-sm text-green-700 mt-1">
                                <i class="fas fa-circle-check mr-1"></i>
                                Connected
                                @if($printify->shop_name)
                                    to <span class="font-semibold">{{ $printify->shop_name }}</span>
                                @endif
                            </p>
                        @else
                            <p class="text-sm text-ink-muted mt-1">
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
                                               text-red-600 border border-red-200 hover:bg-red-50 transition-colors">
                                    <i class="fas fa-unlink"></i> Disconnect
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                @if(session('printify_success'))
                    <div class="mt-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded">
                        <i class="fas fa-circle-check mr-1"></i>{{ session('printify_success') }}
                    </div>
                @endif
                @if($errors->has('api_token'))
                    <div class="mt-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded">
                        <i class="fas fa-circle-exclamation mr-1"></i>{{ $errors->first('api_token') }}
                    </div>
                @endif
            </div>

            {{-- ── Stats Grid ── --}}
            <div class="bg-white border border-cream-200 sm:rounded-lg p-6 sm:p-8">
                <h3 class="font-medium text-ink text-base mb-5 flex items-center gap-2">
                    <i class="fas fa-chart-bar text-ink-muted"></i>
                    Your Activity
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Most used model --}}
                    <div class="flex items-start gap-4 p-4 bg-cream-50 border border-cream-200 rounded-lg">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-violet-100 shrink-0">
                            <i class="fas fa-microchip text-violet-500"></i>
                        </span>
                        <div>
                            <p class="text-xs text-ink-muted uppercase tracking-wide font-medium">Most Used Model</p>
                            <p class="text-xl font-bold text-ink mt-0.5">
                                @php
                                    $modelLabels = [
                                        'fabric_light'    => 'Fabric Light',
                                        'fabric_pro'      => 'Fabric Pro',
                                        'z_image_turbo'   => 'Z Image Turbo',
                                        'flux_schnell'    => 'Flux Schnell',
                                    ];
                                @endphp
                                {{ $modelLabels[$stats['most_used_model']] ?? $stats['most_used_model'] }}
                            </p>
                        </div>
                    </div>

                    {{-- Tokens spent --}}
                    <div class="flex items-start gap-4 p-4 bg-cream-50 border border-cream-200 rounded-lg">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-amber-100 shrink-0">
                            <i class="fas fa-coins text-amber-500"></i>
                        </span>
                        <div>
                            <p class="text-xs text-ink-muted uppercase tracking-wide font-medium">Tokens Spent</p>
                            <p class="text-xl font-bold text-ink mt-0.5">{{ number_format($stats['tokens_used']) }}</p>
                        </div>
                    </div>

                    {{-- Images generated --}}
                    <div class="flex items-start gap-4 p-4 bg-cream-50 border border-cream-200 rounded-lg">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-sky-100 shrink-0">
                            <i class="fas fa-image text-sky-500"></i>
                        </span>
                        <div>
                            <p class="text-xs text-ink-muted uppercase tracking-wide font-medium">Images Generated</p>
                            <p class="text-xl font-bold text-ink mt-0.5">{{ number_format($stats['images_generated']) }}</p>
                        </div>
                    </div>

                    {{-- Uploaded to Printify --}}
                    <div class="flex items-start gap-4 p-4 bg-cream-50 border border-cream-200 rounded-lg">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-orange-100 shrink-0">
                            <i class="fas fa-shirt text-[#FF4D00]"></i>
                        </span>
                        <div>
                            <p class="text-xs text-ink-muted uppercase tracking-wide font-medium">Uploaded to Printify</p>
                            <p class="text-xl font-bold text-ink mt-0.5">{{ number_format($stats['products_pushed']) }}</p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── Tokens & Plan Grid ── --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                {{-- Tokens column --}}
                <div class="bg-white border border-cream-200 sm:rounded-lg p-6 sm:p-8 flex flex-col gap-5">
                    <div>
                        <h3 class="font-medium text-ink text-base flex items-center gap-2 mb-1">
                            <i class="fas fa-coins text-amber-500"></i>
                            Tokens
                        </h3>
                        <p class="text-xs text-ink-muted">Use tokens to generate designs. They never expire.</p>
                    </div>

                    {{-- Current balance --}}
                    <div class="flex items-end gap-3 px-4 py-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <span class="font-serif text-4xl font-bold text-amber-600">{{ number_format($user->tokens ?? 0) }}</span>
                        <span class="text-sm text-amber-700 pb-1">tokens remaining</span>
                    </div>

                    {{-- Buy more label --}}
                    <p class="text-xs font-semibold text-ink uppercase tracking-wide">Buy more tokens</p>

                    {{-- Token packs --}}
                    <div class="flex flex-col gap-3">
                        @foreach([
                            ['amount' => 100,  'price' => '€2',  'label' => 'Starter Pack',   'popular' => false],
                            ['amount' => 500,  'price' => '€8',  'label' => 'Creator Pack',   'popular' => true],
                            ['amount' => 1000, 'price' => '€14', 'label' => 'Studio Pack',    'popular' => false],
                        ] as $pack)
                        <div class="relative flex items-center justify-between px-4 py-3
                                    border rounded-lg transition-colors
                                    {{ $pack['popular']
                                        ? 'border-amber-400 bg-amber-50'
                                        : 'border-cream-300 bg-white hover:bg-cream-50' }}">
                            @if($pack['popular'])
                                <span class="absolute -top-2.5 left-4 text-[10px] font-semibold uppercase tracking-wide
                                             bg-amber-400 text-white px-2 py-0.5 rounded-full">
                                    Best value
                                </span>
                            @endif
                            <div>
                                <p class="text-sm font-semibold text-ink">{{ $pack['amount'] }} tokens</p>
                                <p class="text-xs text-ink-muted">{{ $pack['label'] }}</p>
                            </div>
                            <button disabled
                                    title="Coming soon"
                                    class="text-xs font-semibold px-4 py-1.5 rounded
                                           {{ $pack['popular']
                                               ? 'bg-amber-400 text-white opacity-60 cursor-not-allowed'
                                               : 'bg-ink text-white opacity-40 cursor-not-allowed' }}">
                                {{ $pack['price'] }}
                            </button>
                        </div>
                        @endforeach
                    </div>

                    <p class="text-[11px] text-ink-muted/70 text-center">Token purchases coming soon.</p>
                </div>

                {{-- Plan column --}}
                @php
                    $plan = strtolower($user->plan ?? 'free');
                    $planMeta = [
                        'free'   => [
                            'label'       => 'Free',
                            'price'       => '€0',
                            'period'      => '/ month',
                            'headerBg'    => 'bg-cream-100',
                            'headerText'  => 'text-ink',
                            'badgeBg'     => 'bg-cream-300 text-ink-muted',
                            'accent'      => 'bg-cream-400',
                            'perks'       => ['5 designs / month', 'Standard quality output', 'Basic prompt styles'],
                            'missing'     => ['Background removal', 'Design history', 'Priority support'],
                            'desc'        => 'Perfect for exploring FabricAI and testing your first ideas.',
                        ],
                        'pro'    => [
                            'label'       => 'Pro',
                            'price'       => '€19',
                            'period'      => '/ month',
                            'headerBg'    => 'bg-ink',
                            'headerText'  => 'text-white',
                            'badgeBg'     => 'bg-purple-500 text-white',
                            'accent'      => 'bg-purple-400',
                            'perks'       => ['100 designs / month', 'High quality output', 'All prompt styles', 'Background removal', 'Full design history'],
                            'missing'     => ['Priority support'],
                            'desc'        => 'Great for freelancers and creators who design regularly.',
                        ],
                        'studio' => [
                            'label'       => 'Studio',
                            'price'       => '€49',
                            'period'      => '/ month',
                            'headerBg'    => 'bg-ink',
                            'headerText'  => 'text-white',
                            'badgeBg'     => 'bg-purple-700 text-white',
                            'accent'      => 'bg-purple-600',
                            'perks'       => ['Unlimited designs', 'Ultra-high quality output', 'All prompt styles + custom', 'Background removal', 'Full design history', 'Priority support'],
                            'missing'     => [],
                            'desc'        => 'Built for studios and teams with high volume needs.',
                        ],
                    ];
                    $meta = $planMeta[$plan] ?? $planMeta['free'];
                @endphp

                <div class="border border-cream-200 sm:rounded-lg overflow-hidden flex flex-col">

                    {{-- Card header --}}
                    <div class="relative {{ $meta['headerBg'] }} px-6 pt-6 pb-8">
                        {{-- Top accent line --}}
                        <div class="absolute top-0 left-0 right-0 h-1 {{ $meta['accent'] }}"></div>

                        <div class="flex items-start justify-between">
                            <div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-widest {{ $meta['badgeBg'] }} mb-3">
                                    {{ $meta['label'] }}
                                </span>
                                <p class="text-[11px] {{ $plan === 'free' ? 'text-ink-muted' : 'text-white/60' }} uppercase tracking-wide font-medium">
                                    Current plan
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="font-serif text-4xl font-bold {{ $meta['headerText'] }}">{{ $meta['price'] }}</span>
                                <span class="text-xs {{ $plan === 'free' ? 'text-ink-muted' : 'text-white/50' }} ml-1">{{ $meta['period'] }}</span>
                            </div>
                        </div>

                        <p class="mt-3 text-sm {{ $plan === 'free' ? 'text-ink-muted' : 'text-white/70' }}">
                            {{ $meta['desc'] }}
                        </p>
                    </div>

                    {{-- Features list --}}
                    <div class="bg-white flex-1 px-6 py-5">
                        <ul class="space-y-2.5">
                            @foreach($meta['perks'] as $perk)
                                <li class="flex items-center gap-2.5 text-sm text-ink">
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-green-100 shrink-0">
                                        <i class="fas fa-check text-green-600" style="font-size:9px;"></i>
                                    </span>
                                    {{ $perk }}
                                </li>
                            @endforeach
                            @foreach($meta['missing'] as $miss)
                                <li class="flex items-center gap-2.5 text-sm text-ink-muted/40">
                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-cream-100 shrink-0">
                                        <i class="fas fa-xmark text-ink-muted/40" style="font-size:9px;"></i>
                                    </span>
                                    {{ $miss }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- CTA --}}
                    <div class="bg-white border-t border-cream-200 px-6 py-4">
                        @if($plan === 'studio')
                            <div class="flex flex-col gap-2">
                                <a href="/pricing"
                                   class="inline-flex w-full items-center justify-center gap-2 px-5 py-2.5 rounded-lg
                                          bg-ink text-white text-sm font-semibold hover:bg-ink-light transition-colors">
                                    <i class="fas fa-repeat text-xs"></i>
                                    Change plan
                                </a>
                                <form method="POST" action="{{ route('subscription.cancel.confirm') }}"
                                      x-data
                                      @submit.prevent="if(confirm('Are you sure you want to cancel your subscription? You will be moved to the Free plan immediately.')) $el.submit()">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex w-full items-center justify-center gap-2 px-5 py-2 rounded-lg
                                                   border border-red-200 text-red-600 text-sm font-medium
                                                   hover:bg-red-50 transition-colors">
                                        <i class="fas fa-circle-xmark text-xs"></i>
                                        Cancel subscription
                                    </button>
                                </form>
                            </div>
                        @elseif($plan === 'pro')
                            <div class="flex flex-col gap-2">
                                <a href="/pricing"
                                   class="inline-flex w-full items-center justify-center gap-2 px-5 py-2.5 rounded-lg
                                          bg-ink text-white text-sm font-semibold hover:bg-ink-light transition-colors">
                                    <i class="fas fa-repeat text-xs"></i>
                                    Change plan
                                </a>
                                <form method="POST" action="{{ route('subscription.cancel.confirm') }}"
                                      x-data
                                      @submit.prevent="if(confirm('Are you sure you want to cancel your subscription? You will be moved to the Free plan immediately.')) $el.submit()">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex w-full items-center justify-center gap-2 px-5 py-2 rounded-lg
                                                   border border-red-200 text-red-600 text-sm font-medium
                                                   hover:bg-red-50 transition-colors">
                                        <i class="fas fa-circle-xmark text-xs"></i>
                                        Cancel subscription
                                    </button>
                                </form>
                            </div>
                        @else
                            <a href="/pricing"
                               class="inline-flex w-full items-center justify-center gap-2 px-5 py-2.5 rounded-lg
                                      bg-ink text-white text-sm font-semibold hover:bg-ink-light transition-colors">
                                <i class="fas fa-arrow-up text-xs"></i>
                                Upgrade plan
                            </a>
                        @endif
                    </div>

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

            <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full p-6 sm:p-8 z-10"
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
                        <h2 class="font-serif text-lg text-ink">Connect to Printify</h2>
                    </div>
                    <button @click="showPrintifyModal = false"
                            class="text-ink-muted hover:text-ink transition-colors p-1 rounded focus:outline-none">
                        <i class="fas fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Tutorial --}}
                <div class="bg-cream-50 border border-cream-200 rounded-lg p-4 mb-5 text-sm text-ink-muted space-y-2">
                    <p class="font-semibold text-ink text-sm mb-2 flex items-center gap-1.5">
                        <i class="fas fa-circle-info text-[#FF4D00]"></i>
                        How to get your Printify API Token
                    </p>
                    <ol class="list-decimal list-inside space-y-1.5 pl-1">
                        <li>Log in to your <span class="font-medium text-ink">Printify</span> account.</li>
                        <li>Go to <span class="font-medium text-ink">My account</span> (top-right corner).</li>
                        <li>Select <span class="font-medium text-ink">Connections</span> in the left sidebar.</li>
                        <li>Under <span class="font-medium text-ink">API access tokens</span>, click <span class="font-medium text-ink">Generate new token</span>.</li>
                        <li>Give it a name (e.g. "FabricAI") and <span class="font-medium text-ink">copy it</span> before closing.</li>
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

                    <label for="api_token" class="block text-sm font-medium text-ink mb-1.5">
                        Your Printify API Token
                    </label>
                    <input type="text"
                           id="api_token"
                           name="api_token"
                           placeholder="pst-xxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                           autocomplete="off"
                           class="w-full border border-cream-300 rounded-lg px-4 py-2.5 text-sm text-ink
                                  focus:outline-none focus:ring-2 focus:ring-[#FF4D00]/40 focus:border-[#FF4D00]
                                  placeholder:text-ink-muted/50 transition"
                           value="{{ old('api_token') }}">

                    <div class="flex justify-end gap-3 mt-5">
                        <button type="button"
                                @click="showPrintifyModal = false"
                                class="px-5 py-2 text-sm font-medium text-ink-muted border border-cream-300
                                       rounded-lg hover:bg-cream-100 transition-colors">
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
