<!DOCTYPE html>
<html lang="en" style="background:#0d0d0d">
<head>
    <link rel="icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pricing — FabricAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes shimmer-move { 0%{background-position:-280% 0} 100%{background-position:280% 0} }
        .gradient-text {
            background: linear-gradient(135deg, #9d5bc7 0%, #c084fc 40%, #7c3ca0 70%, #9d5bc7 100%);
            background-size: 280% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
            animation: shimmer-move 5s linear infinite;
        }
        .factory-grid {
            background-image: linear-gradient(rgba(255,255,255,0.03) 1px,transparent 1px),
                              linear-gradient(90deg,rgba(255,255,255,0.03) 1px,transparent 1px);
            background-size: 72px 72px;
        }
        .plan-card { transition: border-color .2s; }
        .plan-card:hover { border-color: rgba(124,60,160,0.35) !important; }
        .check { color: #9d5bc7; margin-right: 6px; }
    </style>
</head>
<body class="bg-[#0d0d0d] text-white font-sans antialiased overflow-x-hidden">
@php $navDarkHero = true; @endphp
@include('layouts.navigation')

<!-- HERO -->
<section class="relative pt-20 pb-16 text-center overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="absolute" style="width:500px;height:500px;top:-150px;right:-80px;border-radius:50%;background:radial-gradient(circle,rgba(124,60,160,0.2) 0%,transparent 60%);filter:blur(100px);pointer-events:none"></div>
    <div class="relative z-10 max-w-2xl mx-auto px-6">
        <h1 class="font-serif leading-tight mb-4" style="font-size:clamp(2.4rem,6vw,4.5rem)">
            Simple, <span class="italic gradient-text">transparent</span> pricing.
        </h1>
        <p class="text-white/45 text-base">Pick a plan. Generate. Unused Spools never expire.</p>
    </div>
</section>

<!-- PLANS -->
<section class="relative pb-20 overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="relative z-10 max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- FREE -->
            <div class="plan-card flex flex-col rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.07)">
                <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-white/30 mb-4">Free</p>
                <div class="mb-1"><span class="font-serif text-4xl text-white">$0</span><span class="text-white/30 text-sm ml-1">/ mo</span></div>
                <p class="text-white/40 text-xs mb-5">5 Spools total &middot; no daily refill</p>
                <ul class="space-y-2 text-sm text-white/50 mb-7 flex-1">
                    <li><span class="check">✓</span>5 designs total</li>
                    <li><span class="check">✓</span>Background removal</li>
                    <li><span class="check">✓</span>Printify integration</li>
                    <li><span class="check">✓</span>Design history</li>
                    <li><span class="check">✓</span>Turbo upload (front)</li>
                    <li><span class="check">✓</span> Fabric Flash (1 spool)</li>
                </ul>
                @auth
                    @if(auth()->user()->plan === 'free')
                        <span class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/35" style="border:1px solid rgba(255,255,255,0.1)">Current plan</span>
                    @else
                        <span class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/20" style="border:1px solid rgba(255,255,255,0.06)">Free tier</span>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/55 hover:text-white transition-colors" style="border:1px solid rgba(255,255,255,0.12)">Get started free</a>
                @endauth
            </div>

            <!-- STARTER -->
            <div class="plan-card flex flex-col rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.07)">
                <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-white/30 mb-4">Starter</p>
                <div class="mb-1"><span class="font-serif text-4xl text-white">$5</span><span class="text-white/30 text-sm ml-1">/ mo</span></div>
                <p class="text-white/40 text-xs mb-5">50 Spools &middot; +1/day &middot; up to 80/mo</p>
                <ul class="space-y-2 text-sm text-white/50 mb-7 flex-1">
                    <li><span class="check">✓</span>Up to 80 designs/mo</li>
                    <li><span class="check">✓</span>Background removal</li>
                    <li><span class="check">✓</span>Printify integration</li>
                    <li><span class="check">✓</span>Design history</li>
                    <li><span class="check">✓</span>Turbo upload (front)</li>
                    <li><span class="check">✓</span>Upload in 1 garment color</li>
                    <li><span class="check">✓</span> Fabric Flash (1 spool)</li>
                </ul>
                @auth
                    @if(auth()->user()->plan === 'starter')
                        <span class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/35" style="border:1px solid rgba(255,255,255,0.1)">Current plan</span>
                    @else
                        <form method="POST" action="{{ route('subscription.checkout') }}">@csrf<input type="hidden" name="plan" value="starter">
                        <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/55 hover:text-white transition-colors cursor-pointer" style="border:1px solid rgba(255,255,255,0.12)">Start with Starter</button></form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/55 hover:text-white transition-colors" style="border:1px solid rgba(255,255,255,0.12)">Start with Starter</a>
                @endauth
            </div>

            <!-- PRO -->
            <div class="plan-card relative flex flex-col rounded-2xl p-6" style="background:linear-gradient(135deg,rgba(124,60,160,0.18) 0%,rgba(90,34,117,0.25) 100%);border:1px solid rgba(124,60,160,0.45);box-shadow:0 0 40px rgba(124,60,160,0.12)">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-[10px] font-semibold uppercase tracking-[.22em]" style="color:#c084fc">Pro</p>
                    <span class="text-[9px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full text-white" style="background:#7c3ca0">Popular</span>
                </div>
                <div class="mb-1"><span class="font-serif text-4xl text-white">$10</span><span class="text-white/30 text-sm ml-1">/ mo</span></div>
                <p class="text-white/45 text-xs mb-5">80 Spools &middot; +4/day &middot; up to 200/mo</p>
                <ul class="space-y-2 text-sm text-white/65 mb-7 flex-1">
                    <li><span class="check">✓</span>Up to 200 designs/mo</li>
                    <li><span class="check">✓</span>Background removal</li>
                    <li><span class="check">✓</span>Printify integration</li>
                    <li><span class="check">✓</span>Design history</li>
                    <li><span class="check">✓</span>10 AI chats/mo</li>
                    <li><span class="check">✓</span>Turbo front &amp; back</li>
                    <li><span class="check">✓</span>Post in all colors</li>
                    <li><span class="check">✓</span> Fabric Flash (1 spool)</li>
                    <li><span class="check">✓</span><span style="color:#c084fc"> Fabric Max (2 spools)</span></li>
                </ul>
                @auth
                    @if(auth()->user()->plan === 'pro')
                        <span class="block text-center py-2.5 rounded-xl text-xs font-semibold uppercase tracking-wider text-white" style="background:rgba(124,60,160,0.4);border:1px solid rgba(124,60,160,0.5)">Current plan</span>
                    @else
                        <form method="POST" action="{{ route('subscription.checkout') }}">@csrf<input type="hidden" name="plan" value="pro">
                        <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-semibold uppercase tracking-wider text-white hover:opacity-90 transition-opacity cursor-pointer" style="background:#7c3ca0">Start with Pro</button></form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block text-center py-2.5 rounded-xl text-xs font-semibold uppercase tracking-wider text-white hover:opacity-90 transition-opacity" style="background:#7c3ca0">Start with Pro</a>
                @endauth
            </div>

            <!-- BUSINESS -->
            <div class="plan-card flex flex-col rounded-2xl p-6" style="background:#111;border:1px solid rgba(255,255,255,0.07)">
                <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-white/30 mb-4">Business</p>
                <div class="mb-1"><span class="font-serif text-4xl text-white">$20</span><span class="text-white/30 text-sm ml-1">/ mo</span></div>
                <p class="text-white/40 text-xs mb-5">200 Spools &middot; +10/day &middot; up to 500/mo</p>
                <ul class="space-y-2 text-sm text-white/50 mb-7 flex-1">
                    <li><span class="check">✓</span>Up to 500 designs/mo</li>
                    <li><span class="check">✓</span>Background removal</li>
                    <li><span class="check">✓</span>Printify integration</li>
                    <li><span class="check">✓</span>Design history</li>
                    <li><span class="check">✓</span>30 AI chats/mo</li>
                    <li><span class="check">✓</span>Turbo front &amp; back</li>
                    <li><span class="check">✓</span>Post in all colors</li>
                    <li><span class="check">✓</span> Fabric Flash (1 spool)</li>
                    <li><span class="check">✓</span><span style="color:#c084fc"> Fabric Max (2 spools)</span></li>
                </ul>
                @auth
                    @if(auth()->user()->plan === 'business')
                        <span class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/35" style="border:1px solid rgba(255,255,255,0.1)">Current plan</span>
                    @else
                        <form method="POST" action="{{ route('subscription.checkout') }}">@csrf<input type="hidden" name="plan" value="business">
                        <button type="submit" class="w-full py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/55 hover:text-white transition-colors cursor-pointer" style="border:1px solid rgba(255,255,255,0.12)">Start with Business</button></form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/55 hover:text-white transition-colors" style="border:1px solid rgba(255,255,255,0.12)">Start with Business</a>
                @endauth
            </div>

        </div>

        <!-- COMPACT COMPARISON TABLE -->
        <div class="mt-16 rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.07)">
            <table class="w-full text-sm">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.07);background:rgba(255,255,255,0.03)">
                        <th class="text-left py-3 px-5 text-white/25 font-medium text-xs uppercase tracking-wider w-2/5"></th>
                        <th class="py-3 px-3 text-white/25 font-medium text-xs uppercase tracking-wider text-center">Free</th>
                        <th class="py-3 px-3 text-white/25 font-medium text-xs uppercase tracking-wider text-center">Starter</th>
                        <th class="py-3 px-3 font-semibold text-xs uppercase tracking-wider text-center" style="color:#c084fc">Pro</th>
                        <th class="py-3 px-3 text-white/25 font-medium text-xs uppercase tracking-wider text-center">Business</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $rows = [
                        ['Spools / month',         '5',    '80',   '200',  '500'],
                        ['Daily refill',           '—',    '+1',   '+4',   '+10'],
                        ['Spools roll over',       false,  true,   true,   true],
                        ['AI chats / month',       '—',    '—',    '10',   '30'],
                        ['Turbo back placement',   false,  false,  true,   true],
                        ['Post in all colors',     false,  false,  true,   true],
                        ['Garment colors (upload)','1',    '1',    'All',  'All'],
                        [' Fabric Flash',         true,   true,   true,   true],
                        [' Fabric Max (2 spools)',false,  false,  true,   true],
                    ];
                    @endphp
                    @foreach($rows as $i => $row)
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.05);{{ $i % 2 !== 0 ? 'background:rgba(255,255,255,0.015)' : '' }}">
                        <td class="py-3 px-5 text-white/45 text-xs">{{ $row[0] }}</td>
                        @for($c = 1; $c <= 4; $c++)
                        <td class="py-3 px-3 text-center text-xs">
                            @if($row[$c] === true)
                                <svg class="w-3.5 h-3.5 mx-auto" fill="none" stroke="#7c3ca0" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @elseif($row[$c] === false)
                                <span style="color:rgba(255,255,255,0.12)">—</span>
                            @else
                                <span style="{{ $c === 3 ? 'color:#c084fc;font-weight:500' : 'color:rgba(255,255,255,0.35)' }}">{{ $row[$c] }}</span>
                            @endif
                        </td>
                        @endfor
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- CTA -->
        <div class="mt-12 text-center">
            <a href="{{ route('designs.form') }}" class="inline-block px-8 py-3 rounded-full text-sm font-semibold text-white hover:opacity-90 transition-opacity" style="background:#7c3ca0">Start designing free</a>
        </div>
    </div>
</section>

@include('layouts.footer')
</body>
</html>
