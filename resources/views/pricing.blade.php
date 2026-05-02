<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/images/logo.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pricing — FabricAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @keyframes float-a {
            0%,100% { transform: translateY(0) rotate(0deg); }
            40%     { transform: translateY(-22px) rotate(1.5deg); }
            70%     { transform: translateY(-10px) rotate(-1deg); }
        }
        @keyframes float-b {
            0%,100% { transform: translateY(0); }
            35%     { transform: translateY(16px); }
            65%     { transform: translateY(7px); }
        }
        @keyframes shimmer-move {
            0%   { background-position: -280% 0; }
            100% { background-position:  280% 0; }
        }
        @keyframes scan-down {
            0%   { top: -3px; opacity: 1; }
            85%  { opacity: 0.5; }
            100% { top: 100%; opacity: 0; }
        }
        @keyframes pulse-ring {
            0%   { transform: scale(1);   opacity: 0.55; }
            100% { transform: scale(2.4); opacity: 0; }
        }
        .orb { position: absolute; border-radius: 50%; pointer-events: none; filter: blur(110px); }
        .orb-a { background: radial-gradient(circle, rgba(124,60,160,0.28) 0%, transparent 60%); }
        .orb-b { background: radial-gradient(circle, rgba(90,34,117,0.18)  0%, transparent 60%); }
        .scan-line {
            position: absolute; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, rgba(157,91,199,0.55), transparent);
            pointer-events: none; animation: scan-down 8s linear infinite;
        }
        .gradient-text {
            background: linear-gradient(135deg, #9d5bc7 0%, #c084fc 35%, #7c3ca0 65%, #9d5bc7 100%);
            background-size: 280% auto;
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; animation: shimmer-move 5s linear infinite;
        }
        .factory-grid {
            background-image:
                linear-gradient(rgba(255,255,255,0.033) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.033) 1px, transparent 1px);
            background-size: 72px 72px;
        }
        .tag-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 5px 14px; border-radius: 999px;
            background: rgba(124,60,160,0.12);
            border: 1px solid rgba(124,60,160,0.3);
            font-size: 10px; font-weight: 600;
            letter-spacing: .22em; text-transform: uppercase; color: #9d5bc7;
        }
        .pulse-dot { position: relative; display: inline-block; }
        .pulse-dot::before {
            content: ''; position: absolute; inset: -5px; border-radius: 50%;
            background: rgba(124,60,160,0.35);
            animation: pulse-ring 2.2s ease-out infinite;
        }
    </style>
</head>
<body class="bg-[#0d0d0d] text-white font-sans antialiased overflow-x-hidden">
@php $navDarkHero = true; @endphp
@include('layouts.navigation')

<!-- HERO -->
<section class="relative pt-20 pb-20 text-center overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="orb orb-a" style="width:600px;height:600px;top:-180px;right:-100px;animation:float-a 10s ease-in-out infinite;"></div>
    <div class="orb orb-b" style="width:400px;height:400px;bottom:0;left:-80px;animation:float-b 13s ease-in-out infinite 2s;"></div>
    <div class="scan-line"></div>
    <div class="absolute top-8 right-8 w-6 h-6 border-t-2 border-r-2 hidden sm:block" style="border-color:rgba(124,60,160,0.4)"></div>
    <div class="absolute top-8 left-8 w-6 h-6 border-t-2 border-l-2 hidden sm:block" style="border-color:rgba(124,60,160,0.4)"></div>
    <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 80)"
         :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
         class="relative z-10 max-w-3xl mx-auto px-6 transition-all duration-1000 ease-out">

        <h1 class="font-serif leading-tight mb-6" style="font-size:clamp(2.8rem,7vw,6rem)">
            <span class="text-white">Simple,</span><br>
            <span class="italic gradient-text">transparent</span><br>
            <span class="text-white">pricing.</span>
        </h1>
        <p class="text-white/50 text-lg max-w-xl mx-auto">
            One prompt. A hundred designs. Pick the plan that fits your creative output.
        </p>
        <p class="text-white/30 text-sm mt-3 max-w-lg mx-auto">
            Unused credits carry over every month — they never expire.
        </p>
    </div>
</section>

<!-- PLANS -->
<section class="relative pb-24 overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="relative z-10 max-w-6xl mx-auto px-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- FREE -->
            <div x-data="{ show: false }" x-intersect.once="show = true"
                 :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                 class="transition-all duration-700 flex flex-col rounded-2xl p-7"
                 style="background:#111;border:1px solid rgba(255,255,255,0.07);">
                <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-white/30 mb-5">Free</p>
                <div class="mb-1"><span class="font-serif text-4xl text-white">$0</span><span class="text-white/30 text-sm ml-1">/ month</span></div>
                <div class="mb-4">
                    <p class="text-white/50 text-sm font-medium">5 credits to start</p>
                    <p class="text-white/25 text-xs mt-0.5">No daily refill &middot; no rollover</p>
                </div>
                <p class="text-white/30 text-xs mb-6">Perfect to explore and test ideas.</p>
                <ul class="space-y-2.5 text-sm text-white/55 mb-8 flex-1">
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>5 designs total</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>All AI models</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Background removal</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Printify integration</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Design history</li>
                </ul>
                @auth
                    @if(auth()->user()->plan === 'free')
                        <span class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/40" style="border:1px solid rgba(255,255,255,0.1)">Current plan</span>
                    @else
                        <span class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/25" style="border:1px solid rgba(255,255,255,0.06)">Free tier</span>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/55 hover:text-white transition-colors" style="border:1px solid rgba(255,255,255,0.12)">Get started free</a>
                @endauth
            </div>

            <!-- STARTER -->
            <div x-data="{ show: false }" x-intersect.once="show = true"
                 :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                 class="transition-all duration-700 delay-100 flex flex-col rounded-2xl p-7"
                 style="background:#111;border:1px solid rgba(255,255,255,0.07);">
                <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-white/30 mb-5">Starter</p>
                <div class="mb-1"><span class="font-serif text-4xl text-white">$5</span><span class="text-white/30 text-sm ml-1">/ month</span></div>
                <div class="mb-4">
                    <p class="text-white/50 text-sm font-medium">50 credits on day 1</p>
                    <p class="text-white/25 text-xs mt-0.5">+1 credit / day &mdash; up to 80 / month</p>
                </div>
                <p class="text-white/30 text-xs mb-6">For creators getting serious about their designs.</p>
                <ul class="space-y-2.5 text-sm text-white/55 mb-8 flex-1">
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Up to 80 designs / month</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>All AI models</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Background removal</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Printify integration</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Design history</li>
                </ul>
                @auth
                    @if(auth()->user()->plan === 'starter')
                        <span class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/40" style="border:1px solid rgba(255,255,255,0.1)">Current plan</span>
                    @else
                        <form method="POST" action="{{ route('subscription.checkout') }}">@csrf<input type="hidden" name="plan" value="starter">
                        <button type="submit" class="w-full text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/55 hover:text-white transition-colors cursor-pointer" style="border:1px solid rgba(255,255,255,0.12)">Start with Starter</button></form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/55 hover:text-white transition-colors" style="border:1px solid rgba(255,255,255,0.12)">Start with Starter</a>
                @endauth
            </div>

            <!-- PRO -->
            <div x-data="{ show: false }" x-intersect.once="show = true"
                 :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                 class="transition-all duration-700 delay-200 relative flex flex-col rounded-2xl p-7"
                 style="background:linear-gradient(135deg,rgba(124,60,160,0.18) 0%,rgba(90,34,117,0.25) 100%);border:1px solid rgba(124,60,160,0.45);box-shadow:0 0 40px rgba(124,60,160,0.15);">
                <div class="flex items-center justify-between mb-5">
                    <p class="text-[10px] font-semibold uppercase tracking-[.22em]" style="color:#c084fc">Pro</p>
                    <span class="text-[9px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full text-white" style="background:#7c3ca0">Most popular</span>
                </div>
                <div class="mb-1"><span class="font-serif text-4xl text-white">$10</span><span class="text-white/30 text-sm ml-1">/ month</span></div>
                <div class="mb-4">
                    <p class="text-white/70 text-sm font-medium">80 credits on day 1</p>
                    <p class="text-white/35 text-xs mt-0.5">+4 credits / day &mdash; up to 200 / month</p>
                </div>
                <p class="text-white/40 text-xs mb-6">For freelancers who design regularly.</p>
                <ul class="space-y-2.5 text-sm text-white/70 mb-8 flex-1">
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#c084fc"></span>Up to 200 designs / month</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#c084fc"></span>All AI models</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#c084fc"></span>Background removal</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#c084fc"></span>Printify integration</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#c084fc"></span>Design history</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#c084fc"></span>10 AI chats / month</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#c084fc"></span>Turbo front &amp; back</li>
                </ul>
                @auth
                    @if(auth()->user()->plan === 'pro')
                        <span class="block text-center py-2.5 rounded-xl text-xs font-semibold uppercase tracking-wider text-white" style="background:rgba(124,60,160,0.4);border:1px solid rgba(124,60,160,0.5)">Current plan</span>
                    @else
                        <form method="POST" action="{{ route('subscription.checkout') }}">@csrf<input type="hidden" name="plan" value="pro">
                        <button type="submit" class="w-full text-center py-2.5 rounded-xl text-xs font-semibold uppercase tracking-wider text-white hover:opacity-90 transition-opacity cursor-pointer" style="background:#7c3ca0">Start with Pro</button></form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block text-center py-2.5 rounded-xl text-xs font-semibold uppercase tracking-wider text-white hover:opacity-90 transition-opacity" style="background:#7c3ca0">Start with Pro</a>
                @endauth
            </div>

            <!-- BUSINESS -->
            <div x-data="{ show: false }" x-intersect.once="show = true"
                 :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
                 class="transition-all duration-700 delay-300 flex flex-col rounded-2xl p-7"
                 style="background:#111;border:1px solid rgba(255,255,255,0.07);">
                <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-white/30 mb-5">Business</p>
                <div class="mb-1"><span class="font-serif text-4xl text-white">$20</span><span class="text-white/30 text-sm ml-1">/ month</span></div>
                <div class="mb-4">
                    <p class="text-white/50 text-sm font-medium">200 credits on day 1</p>
                    <p class="text-white/25 text-xs mt-0.5">+10 credits / day &mdash; up to 500 / month</p>
                </div>
                <p class="text-white/30 text-xs mb-6">For studios and teams with high-volume needs.</p>
                <ul class="space-y-2.5 text-sm text-white/55 mb-8 flex-1">
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Up to 500 designs / month</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>All AI models</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Background removal</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Printify integration</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Design history</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>30 AI chats / month</li>
                    <li class="flex items-center gap-2.5"><span class="w-1 h-1 rounded-full flex-shrink-0" style="background:#7c3ca0"></span>Turbo front &amp; back</li>
                </ul>
                @auth
                    @if(auth()->user()->plan === 'business')
                        <span class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/40" style="border:1px solid rgba(255,255,255,0.1)">Current plan</span>
                    @else
                        <form method="POST" action="{{ route('subscription.checkout') }}">@csrf<input type="hidden" name="plan" value="business">
                        <button type="submit" class="w-full text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/55 hover:text-white transition-colors cursor-pointer" style="border:1px solid rgba(255,255,255,0.12)">Start with Business</button></form>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="block text-center py-2.5 rounded-xl text-xs font-medium uppercase tracking-wider text-white/55 hover:text-white transition-colors" style="border:1px solid rgba(255,255,255,0.12)">Start with Business</a>
                @endauth
            </div>

        </div>
    </div>
</section>

<!-- COMPARISON TABLE -->
<section class="relative py-24 overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="relative z-10 max-w-5xl mx-auto px-6">
        <h2 class="font-serif text-center text-3xl sm:text-4xl text-white mb-14">Compare plans</h2>
        <div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.07);">
            <table class="w-full text-sm">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.07);background:rgba(255,255,255,0.03);">
                        <th class="text-left py-4 px-6 text-white/30 font-medium text-xs uppercase tracking-wider w-2/5">Feature</th>
                        <th class="text-center py-4 px-4 text-white/30 font-medium text-xs uppercase tracking-wider">Free</th>
                        <th class="text-center py-4 px-4 text-white/30 font-medium text-xs uppercase tracking-wider">Starter</th>
                        <th class="text-center py-4 px-4 font-semibold text-xs uppercase tracking-wider" style="color:#c084fc">Pro</th>
                        <th class="text-center py-4 px-4 text-white/30 font-medium text-xs uppercase tracking-wider">Business</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $rows = [
                        ['Credits on day 1',     '5',                  '50',                 '80',                 '200'],
                        ['Daily refill',         false,                '+1 / day',           '+4 / day',           '+10 / day'],
                        ['Monthly cap',          '5',                  '80',                 '200',                '500'],
                        ['Credits roll over',    false,                true,                 true,                 true],
                        ['All AI models',        true,                 true,                 true,                 true],
                        ['Background removal',   true,                 true,                 true,                 true],
                        ['Printify integration', true,                 true,                 true,                 true],
                        ['Design history',       true,                 true,                 true,                 true],
                        ['AI chats / month',     false,                false,                '10',                 '30'],
                        ['Turbo front & back',   false,                false,                true,                 true],
                    ];
                    @endphp
                    @foreach($rows as $i => $row)
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.05);{{ $i % 2 !== 0 ? 'background:rgba(255,255,255,0.015);' : '' }}">
                        <td class="py-4 px-6 text-white/50">{{ $row[0] }}</td>
                        @for($c = 1; $c <= 4; $c++)
                        <td class="py-4 px-4 text-center">
                            @if($row[$c] === true)
                                <svg class="w-4 h-4 mx-auto" fill="none" stroke="#7c3ca0" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @elseif($row[$c] === false)
                                <span style="color:rgba(255,255,255,0.15)">—</span>
                            @else
                                <span style="{{ $c === 3 ? 'color:#c084fc;font-weight:500' : 'color:rgba(255,255,255,0.4)' }}">{{ $row[$c] }}</span>
                            @endif
                        </td>
                        @endfor
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- FAQ CTA -->
<section class="relative py-28 text-center overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="orb orb-a" style="width:500px;height:500px;bottom:-100px;left:50%;transform:translateX(-50%);"></div>
    <div class="relative z-10 max-w-xl mx-auto px-6">
        <p class="text-white/30 text-sm mb-3">Have questions?</p>
        <h3 class="font-serif text-3xl sm:text-4xl text-white mb-4">Not sure which plan is right for you?</h3>
        <p class="text-white/40 text-sm mb-10">Check out our FAQ or jump straight in — no commitment needed.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="/faq" class="px-8 py-3 rounded-full text-sm font-medium text-white/70 hover:text-white transition-colors" style="border:1px solid rgba(255,255,255,0.15)">View FAQ</a>
            <a href="{{ route('designs.form') }}" class="px-8 py-3 rounded-full text-sm font-semibold text-white hover:opacity-90 transition-opacity" style="background:#7c3ca0">Start designing free</a>
        </div>
    </div>
</section>

@include('layouts.footer')
</body>
</html>
