<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing — FabricAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-950 text-white overflow-x-hidden">

@include('layouts.navigation')

<!-- ================= HERO ================= -->
<section class="relative pt-36 pb-20 text-center overflow-hidden">

    <!-- Background glow -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2
                w-[900px] h-[600px] bg-purple-600/10 blur-[140px] rounded-full pointer-events-none"></div>

    <div class="relative max-w-3xl mx-auto px-6">
        <p class="text-purple-400 font-semibold tracking-widest uppercase text-sm mb-4">Pricing</p>
        <h1 class="text-5xl sm:text-6xl font-bold leading-tight mb-6">
            Simple, transparent<br>
            <span class="bg-gradient-to-r from-purple-400 to-indigo-400 bg-clip-text text-transparent">pricing</span>
        </h1>
        <p class="text-gray-400 text-lg max-w-xl mx-auto">
            Pick the plan that fits your creative needs. Upgrade or downgrade at any time, no surprises.
        </p>

        <!-- Toggle monthly / yearly -->
        <div
            x-data="{ yearly: false }"
            class="mt-10"
        >
            <div class="inline-flex items-center gap-3 bg-gray-900 border border-gray-800 rounded-xl px-4 py-2">
                <span :class="!yearly ? 'text-white' : 'text-gray-500'" class="text-sm font-medium transition">Monthly</span>
                <button
                    @click="yearly = !yearly"
                    class="relative w-12 h-6 rounded-full bg-gray-700 transition-colors duration-300"
                    :class="yearly ? 'bg-purple-600' : 'bg-gray-700'"
                >
                    <span
                        class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-300"
                        :class="yearly ? 'translate-x-6' : 'translate-x-0'"
                    ></span>
                </button>
                <span :class="yearly ? 'text-white' : 'text-gray-500'" class="text-sm font-medium transition">
                    Yearly
                    <span class="ml-1 text-xs bg-purple-500/20 text-purple-400 border border-purple-500/30 px-1.5 py-0.5 rounded-full">
                        –20%
                    </span>
                </span>
            </div>

            <!-- ================= PLANS ================= -->
            <div class="mt-14 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">

                <!-- FREE -->
                <div class="flex flex-col rounded-2xl bg-gray-900 border border-gray-800 p-8 hover:border-gray-700 transition-all duration-300">
                    <p class="text-sm font-semibold text-gray-400 uppercase tracking-widest mb-4">Free</p>
                    <div class="mb-6">
                        <span class="text-5xl font-bold">€0</span>
                        <span class="text-gray-500 text-sm ml-2">/ month</span>
                    </div>
                    <p class="text-gray-400 text-sm mb-8">Perfect to explore FabricAI and test your first ideas.</p>

                    <ul class="space-y-3 text-sm text-gray-300 mb-10 flex-1">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            5 designs / month
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Standard quality output
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Basic prompt styles
                        </li>
                        <li class="flex items-center gap-2 opacity-40">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Background removal
                        </li>
                        <li class="flex items-center gap-2 opacity-40">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Design history
                        </li>
                        <li class="flex items-center gap-2 opacity-40">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Priority support
                        </li>
                    </ul>

                    <a href="{{ route('login') }}"
                       class="block text-center py-3 rounded-xl border border-gray-700 text-sm font-semibold text-gray-300 hover:border-purple-500 hover:text-white transition-all duration-300">
                        Get started free
                    </a>
                </div>

                <!-- PRO (highlighted) -->
                <div class="relative flex flex-col rounded-2xl border p-8 transition-all duration-300
                            bg-gradient-to-b from-purple-950/60 to-gray-900 border-purple-500/50
                            shadow-xl shadow-purple-500/10
                            hover:shadow-purple-500/20 hover:border-purple-400/70 scale-105">

                    <!-- Popular badge -->
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full
                                bg-gradient-to-r from-purple-500 to-indigo-500
                                text-xs font-bold uppercase tracking-widest text-white shadow-lg">
                        Most popular
                    </div>

                    <p class="text-sm font-semibold text-purple-400 uppercase tracking-widest mb-4">Pro</p>
                    <div class="mb-6">
                        <span class="text-5xl font-bold" x-text="yearly ? '€15' : '€19'">€19</span>
                        <span class="text-gray-500 text-sm ml-2">/ month</span>
                        <p x-show="yearly" class="text-xs text-purple-400 mt-1" style="display:none;">Billed €180 / year</p>
                    </div>
                    <p class="text-gray-400 text-sm mb-8">For freelancers and creators who design regularly.</p>

                    <ul class="space-y-3 text-sm text-gray-300 mb-10 flex-1">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            100 designs / month
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            High quality output
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            All prompt styles
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Background removal
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Full design history
                        </li>
                        <li class="flex items-center gap-2 opacity-40">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Priority support
                        </li>
                    </ul>

                    <a href="{{ route('login') }}"
                       class="block text-center py-3 rounded-xl font-semibold text-sm text-white
                              bg-gradient-to-r from-purple-500 to-indigo-500
                              shadow-lg shadow-purple-500/30
                              hover:opacity-90 hover:scale-105 transition-all duration-300">
                        Start with Pro
                    </a>
                </div>

                <!-- STUDIO -->
                <div class="flex flex-col rounded-2xl bg-gray-900 border border-gray-800 p-8 hover:border-gray-700 transition-all duration-300">
                    <p class="text-sm font-semibold text-gray-400 uppercase tracking-widest mb-4">Studio</p>
                    <div class="mb-6">
                        <span class="text-5xl font-bold" x-text="yearly ? '€39' : '€49'">€49</span>
                        <span class="text-gray-500 text-sm ml-2">/ month</span>
                        <p x-show="yearly" class="text-xs text-purple-400 mt-1" style="display:none;">Billed €468 / year</p>
                    </div>
                    <p class="text-gray-400 text-sm mb-8">For studios and teams with high volume needs.</p>

                    <ul class="space-y-3 text-sm text-gray-300 mb-10 flex-1">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Unlimited designs
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Ultra-high quality output
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            All prompt styles + custom
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Background removal
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Full design history
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Priority support
                        </li>
                    </ul>

                    <a href="{{ route('login') }}"
                       class="block text-center py-3 rounded-xl border border-gray-700 text-sm font-semibold text-gray-300 hover:border-purple-500 hover:text-white transition-all duration-300">
                        Start with Studio
                    </a>
                </div>

            </div>

            <!-- ================= COMPARISON TABLE ================= -->
            <div class="mt-28 max-w-4xl mx-auto px-6">
                <h2 class="text-3xl font-bold text-center mb-12 bg-gradient-to-r from-purple-400 to-indigo-400 bg-clip-text text-transparent">
                    Compare plans
                </h2>

                <div class="rounded-2xl border border-gray-800 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-800 bg-gray-900/80">
                                <th class="text-left py-4 px-6 text-gray-400 font-medium w-1/2">Feature</th>
                                <th class="text-center py-4 px-4 text-gray-400 font-medium">Free</th>
                                <th class="text-center py-4 px-4 text-purple-400 font-semibold">Pro</th>
                                <th class="text-center py-4 px-4 text-gray-400 font-medium">Studio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/60">
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
                            <tr class="hover:bg-gray-900/40 transition">
                                <td class="py-4 px-6 text-gray-300">{{ $row[0] }}</td>
                                @for($i = 1; $i <= 3; $i++)
                                <td class="py-4 px-4 text-center">
                                    @if(is_bool($row[$i]))
                                        @if($row[$i])
                                            <svg class="w-5 h-5 text-purple-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        @else
                                            <svg class="w-4 h-4 text-gray-700 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        @endif
                                    @else
                                        <span class="{{ $i === 2 ? 'text-purple-300 font-semibold' : 'text-gray-400' }}">{{ $row[$i] }}</span>
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
                <p class="text-gray-500 text-sm">Have questions?</p>
                <h3 class="text-2xl font-bold mt-2 mb-4">Still not sure which plan is right for you?</h3>
                <p class="text-gray-400 text-sm mb-6">Check out our FAQ or reach out — we're happy to help you choose.</p>
                <a href="/faq"
                   class="inline-block px-6 py-3 rounded-xl border border-gray-700 text-sm font-semibold text-gray-300 hover:border-purple-500 hover:text-white transition-all duration-300">
                    View FAQ
                </a>
            </div>

        </div><!-- end x-data -->
    </div>
</section>

@include('layouts.footer')

</body>
</html>
