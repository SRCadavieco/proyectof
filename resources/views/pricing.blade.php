<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing — FabricAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-cream-50 text-ink font-sans antialiased overflow-x-hidden">

@include('layouts.navigation')

<!-- ================= HERO ================= -->
<section class="pt-36 pb-16 text-center">
    <div class="max-w-3xl mx-auto px-6">
        <p class="text-sm font-medium tracking-widest uppercase text-ink-muted mb-4">Pricing</p>
        <h1 class="font-serif text-5xl sm:text-6xl leading-tight mb-6">
            Simple, transparent<br>
            <span class="italic text-purple-700">pricing</span>
        </h1>
        <p class="text-ink-muted text-lg max-w-xl mx-auto">
            Pick the plan that fits your creative needs. Upgrade or downgrade at any time.
        </p>

        <!-- Toggle monthly / yearly -->
        <div
            x-data="{ yearly: false }"
            class="mt-10"
        >
            <div class="inline-flex items-center gap-3 bg-white border border-cream-300 rounded-full px-5 py-2.5">
                <span :class="!yearly ? 'text-ink' : 'text-ink-muted'" class="text-sm font-medium transition-colors">Monthly</span>
                <button
                    @click="yearly = !yearly"
                    class="relative w-12 h-6 rounded-full transition-colors duration-300"
                    :class="yearly ? 'bg-purple-600' : 'bg-cream-400'"
                >
                    <span
                        class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-300"
                        :class="yearly ? 'translate-x-6' : 'translate-x-0'"
                    ></span>
                </button>
                <span :class="yearly ? 'text-ink' : 'text-ink-muted'" class="text-sm font-medium transition-colors">
                    Yearly
                    <span class="ml-1 text-xs bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded-full">
                        –20%
                    </span>
                </span>
            </div>

            <!-- ================= PLANS ================= -->
            <div class="mt-14 grid grid-cols-1 md:grid-cols-3 gap-px bg-cream-300 max-w-5xl mx-auto border border-cream-300">

                <!-- FREE -->
                <div class="flex flex-col bg-white p-10 text-left">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-widest mb-6">Free</p>
                    <div class="mb-6">
                        <span class="font-serif text-5xl">€0</span>
                        <span class="text-ink-muted text-sm ml-1">/ month</span>
                    </div>
                    <p class="text-ink-muted text-sm mb-8">Perfect to explore FabricAI and test your first ideas.</p>

                    <ul class="space-y-3 text-sm text-ink-light mb-10 flex-1">
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-ink rounded-full shrink-0"></span>
                            5 designs / month
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-ink rounded-full shrink-0"></span>
                            Standard quality output
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-ink rounded-full shrink-0"></span>
                            Basic prompt styles
                        </li>
                        <li class="flex items-center gap-3 text-ink-muted/50">
                            <span class="w-1 h-1 bg-cream-400 rounded-full shrink-0"></span>
                            Background removal
                        </li>
                        <li class="flex items-center gap-3 text-ink-muted/50">
                            <span class="w-1 h-1 bg-cream-400 rounded-full shrink-0"></span>
                            Design history
                        </li>
                        <li class="flex items-center gap-3 text-ink-muted/50">
                            <span class="w-1 h-1 bg-cream-400 rounded-full shrink-0"></span>
                            Priority support
                        </li>
                    </ul>

                    @auth
                        @if(auth()->user()->plan === 'free')
                            <span class="block text-center py-3 border border-ink text-sm font-medium text-ink uppercase tracking-wide">
                                Current plan
                            </span>
                        @else
                            <span class="block text-center py-3 border border-cream-300 text-sm font-medium text-ink-muted uppercase tracking-wide">
                                Free tier
                            </span>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-outline text-center text-xs">
                            Get started free
                        </a>
                    @endauth
                </div>

                <!-- PRO (highlighted) -->
                <div class="relative flex flex-col bg-ink text-white p-10 text-left">

                    <!-- Popular badge -->
                    <div class="absolute -top-px left-0 right-0 h-1 bg-purple-600"></div>
                    <div class="inline-flex self-start px-3 py-1 bg-purple-600 text-white text-xs font-medium uppercase tracking-widest mb-6">
                        Most popular
                    </div>

                    <div class="mb-6">
                        <span class="font-serif text-5xl" x-text="yearly ? '€15' : '€19'">€19</span>
                        <span class="text-white/50 text-sm ml-1">/ month</span>
                        <p x-show="yearly" class="text-xs text-purple-300 mt-1" style="display:none;">Billed €180 / year</p>
                    </div>
                    <p class="text-white/60 text-sm mb-8">For freelancers and creators who design regularly.</p>

                    <ul class="space-y-3 text-sm text-white/80 mb-10 flex-1">
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-purple-400 rounded-full shrink-0"></span>
                            100 designs / month
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-purple-400 rounded-full shrink-0"></span>
                            High quality output
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-purple-400 rounded-full shrink-0"></span>
                            All prompt styles
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-purple-400 rounded-full shrink-0"></span>
                            Background removal
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-purple-400 rounded-full shrink-0"></span>
                            Full design history
                        </li>
                        <li class="flex items-center gap-3 text-white/30">
                            <span class="w-1 h-1 bg-white/20 rounded-full shrink-0"></span>
                            Priority support
                        </li>
                    </ul>

                    @auth
                        @if(auth()->user()->plan === 'pro')
                            <span class="block text-center py-3 bg-white text-ink text-sm font-medium uppercase tracking-wide">
                                Current plan
                            </span>
                        @else
                            <form method="POST" action="{{ route('subscription.checkout') }}">
                                @csrf
                                <input type="hidden" name="plan" value="pro">
                                <input type="hidden" name="billing" :value="yearly ? 'yearly' : 'monthly'">
                                <button type="submit"
                                   class="w-full block text-center py-3 bg-white text-ink text-sm font-medium uppercase tracking-wide
                                          hover:bg-cream-100 transition-colors duration-300 cursor-pointer">
                                    Start with Pro
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login') }}"
                           class="block text-center py-3 bg-white text-ink text-sm font-medium uppercase tracking-wide
                                  hover:bg-cream-100 transition-colors duration-300">
                            Start with Pro
                        </a>
                    @endauth
                </div>

                <!-- STUDIO -->
                <div class="flex flex-col bg-white p-10 text-left">
                    <p class="text-xs font-medium text-ink-muted uppercase tracking-widest mb-6">Studio</p>
                    <div class="mb-6">
                        <span class="font-serif text-5xl" x-text="yearly ? '€39' : '€49'">€49</span>
                        <span class="text-ink-muted text-sm ml-1">/ month</span>
                        <p x-show="yearly" class="text-xs text-purple-600 mt-1" style="display:none;">Billed €468 / year</p>
                    </div>
                    <p class="text-ink-muted text-sm mb-8">For studios and teams with high volume needs.</p>

                    <ul class="space-y-3 text-sm text-ink-light mb-10 flex-1">
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-ink rounded-full shrink-0"></span>
                            Unlimited designs
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-ink rounded-full shrink-0"></span>
                            Ultra-high quality output
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-ink rounded-full shrink-0"></span>
                            All prompt styles + custom
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-ink rounded-full shrink-0"></span>
                            Background removal
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-ink rounded-full shrink-0"></span>
                            Full design history
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-1 h-1 bg-ink rounded-full shrink-0"></span>
                            Priority support
                        </li>
                    </ul>

                    @auth
                        @if(auth()->user()->plan === 'studio')
                            <span class="block text-center py-3 border border-ink text-sm font-medium text-ink uppercase tracking-wide">
                                Current plan
                            </span>
                        @else
                            <form method="POST" action="{{ route('subscription.checkout') }}">
                                @csrf
                                <input type="hidden" name="plan" value="studio">
                                <input type="hidden" name="billing" :value="yearly ? 'yearly' : 'monthly'">
                                <button type="submit"
                                   class="btn-outline w-full text-center text-xs cursor-pointer">
                                    Start with Studio
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-outline text-center text-xs">
                            Start with Studio
                        </a>
                    @endauth
                </div>

            </div>

            <!-- ================= COMPARISON TABLE ================= -->
            <div class="mt-28 max-w-4xl mx-auto px-6">
                <h2 class="font-serif text-3xl text-center mb-12">
                    Compare plans
                </h2>

                <div class="border border-cream-300 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-cream-300 bg-cream-100">
                                <th class="text-left py-4 px-6 text-ink-muted font-medium text-xs uppercase tracking-wider w-1/2">Feature</th>
                                <th class="text-center py-4 px-4 text-ink-muted font-medium text-xs uppercase tracking-wider">Free</th>
                                <th class="text-center py-4 px-4 text-purple-700 font-semibold text-xs uppercase tracking-wider">Pro</th>
                                <th class="text-center py-4 px-4 text-ink-muted font-medium text-xs uppercase tracking-wider">Studio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cream-200">
                            @php
                            $rows = [
                                ['Designs per month',       '5',        '100',        'Unlimited'],
                                ['Output quality',          'Standard', 'High',       'Ultra-high'],
                                ['Prompt styles',           'Basic',    'All',        'All + custom'],
                                ['Background removal',      false,      true,         true],
                                ['Design history',          false,      true,         true],
                                ['Priority support',        false,      false,        true],
                                ['API access',              false,      false,        true],
                                ['Team seats',              '1',        '1',          'Up to 10'],
                                ['Commercial license',      false,      true,         true],
                            ];
                            @endphp

                            @foreach($rows as $row)
                            <tr class="hover:bg-cream-100/50 transition-colors">
                                <td class="py-4 px-6 text-ink-light">{{ $row[0] }}</td>
                                @for($i = 1; $i <= 3; $i++)
                                <td class="py-4 px-4 text-center">
                                    @if(is_bool($row[$i]))
                                        @if($row[$i])
                                            <svg class="w-4 h-4 text-purple-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <span class="text-cream-400">—</span>
                                        @endif
                                    @else
                                        <span class="{{ $i === 2 ? 'text-purple-700 font-medium' : 'text-ink-muted' }}">{{ $row[$i] }}</span>
                                    @endif
                                </td>
                                @endfor
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ================= FAQ TEASER ================= -->
            <div class="mt-28 max-w-2xl mx-auto px-6 text-center pb-24">
                <p class="text-ink-muted text-sm">Have questions?</p>
                <h3 class="font-serif text-2xl mt-2 mb-4">Still not sure which plan is right for you?</h3>
                <p class="text-ink-muted text-sm mb-8">Check out our FAQ or reach out — we're happy to help you choose.</p>
                <a href="/faq" class="btn-outline text-xs">
                    View FAQ
                </a>
            </div>

        </div><!-- end x-data -->
    </div>
</section>

@include('layouts.footer')

</body>
</html>
