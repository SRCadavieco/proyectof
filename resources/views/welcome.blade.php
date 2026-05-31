<!DOCTYPE html>
<html lang="en" style="background:#1a1a1a">
<head>
    <link rel="icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Clothing Design Generator | Print on Demand | FabricAI</title>
    <meta name="description" content="Create AI clothing designs in seconds. FabricAI is an AI design generator for print on demand, t-shirt design, apparel graphics, and Printify-ready dropshipping products.">
    <meta name="keywords" content="AI clothing design, AI design generator, print on demand, t-shirt design, apparel graphics, Printify, dropshipping, product design, POD automation">
    <link rel="canonical" href="https://fabricai.net">
    <meta property="og:type" content="website">
    <meta property="og:title" content="AI Clothing Design Generator for Print on Demand | FabricAI">
    <meta property="og:description" content="Generate AI apparel designs, remove backgrounds, and publish Printify-ready products in seconds.">
    <meta property="og:url" content="https://fabricai.net">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FabricAI | AI Clothing Design Generator">
    <meta name="twitter:description" content="AI designs for print on demand, t-shirts, and apparel collections.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── Keyframes ── */
        @keyframes float-a {
            0%,100% { transform: translateY(0)    rotate(0deg); }
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
        @keyframes count-pop {
            0%   { opacity: 0; transform: translateY(16px) scale(0.82); }
            65%  { transform: translateY(-4px) scale(1.05); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes btn-glow {
            0%,100% { box-shadow: 0 0 30px rgba(124,60,160,0.3); }
            50%     { box-shadow: 0 0 55px rgba(124,60,160,0.55); }
        }
        @keyframes line-draw {
            from { transform: scaleX(0); transform-origin: left; }
            to   { transform: scaleX(1);  transform-origin: left; }
        }

        /* ── Orbs ── */
        .orb {
            position: absolute; border-radius: 50%;
            pointer-events: none; filter: blur(110px);
        }
        .orb-a { background: radial-gradient(circle, rgba(124,60,160,0.28) 0%, transparent 60%); }
        .orb-b { background: radial-gradient(circle, rgba(90,34,117,0.18)  0%, transparent 60%); }

        /* ── Scan line ── */
        .scan-line {
            position: absolute; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, rgba(157,91,199,0.55), transparent);
            pointer-events: none;
            animation: scan-down 8s linear infinite;
        }

        /* ── Gradient shimmer text ── */
        .gradient-text {
            background: linear-gradient(135deg, #9d5bc7 0%, #c084fc 35%, #7c3ca0 65%, #9d5bc7 100%);
            background-size: 280% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: shimmer-move 5s linear infinite;
        }

        /* ── Industrial grid ── */
        .factory-grid {
            background-image:
                linear-gradient(rgba(255,255,255,0.033) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.033) 1px, transparent 1px);
            background-size: 72px 72px;
        }
        .factory-grid-light {
            background-image:
                linear-gradient(rgba(26,26,26,0.042) 1px, transparent 1px),
                linear-gradient(90deg, rgba(26,26,26,0.042) 1px, transparent 1px);
            background-size: 72px 72px;
        }

        /* ── Corner marks ── */
        .corner-marks::before, .corner-marks::after {
            content: ''; position: absolute;
            width: 26px; height: 26px;
            border-color: rgba(124,60,160,0.5); border-style: solid;
        }
        .corner-marks::before { top: 28px; left: 28px; border-width: 2px 0 0 2px; }
        .corner-marks::after  { bottom: 28px; right: 28px; border-width: 0 2px 2px 0; }
        @media (max-width:640px) { .corner-marks::before, .corner-marks::after { display: none; } }

        /* ── Pulsing dot ── */
        .pulse-dot { position: relative; display: inline-block; }
        .pulse-dot::before {
            content: ''; position: absolute; inset: -5px; border-radius: 50%;
            background: rgba(124,60,160,0.35);
            animation: pulse-ring 2.2s ease-out infinite;
        }

        /* ── Button shimmer sweep ── */
        .btn-shimmer { position: relative; overflow: hidden; }
        .btn-shimmer::after {
            content: ''; position: absolute;
            top: 0; left: -80%; width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.22), transparent);
            animation: shimmer-move 2.8s ease-in-out infinite;
        }

        /* ── Card lift with purple glow ── */
        .card-lift {
            transition: transform 0.35s cubic-bezier(.34,1.5,.64,1), box-shadow 0.35s ease;
        }
        .card-lift:hover {
            transform: translateY(-7px);
            box-shadow: 0 22px 55px -10px rgba(124,60,160,0.2);
        }

        /* ── Icon bounce on group hover ── */
        .icon-bounce { transition: transform 0.35s cubic-bezier(.34,1.5,.64,1); }
        .group:hover .icon-bounce { transform: rotate(14deg) scale(1.2); }

        /* ── Stat animation ── */
        .stat-num {
            animation: count-pop 0.65s cubic-bezier(.34,1.5,.64,1) forwards;
        }
        .stat-d1 { animation-delay: 0.15s; }
        .stat-d2 { animation-delay: 0.30s; }
        .stat-d3 { animation-delay: 0.45s; }

        /* ── Stagger transition delays ── */
        .stagger-1 { transition-delay: 0s; }
        .stagger-2 { transition-delay: 0.10s; }
        .stagger-3 { transition-delay: 0.20s; }
        .stagger-4 { transition-delay: 0.30s; }

        /* ── Tag pill ── */
        .tag-pill {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 5px 14px; border-radius: 999px;
            background: rgba(124,60,160,0.12);
            border: 1px solid rgba(124,60,160,0.3);
            font-size: 10px; font-weight: 600;
            letter-spacing: .22em; text-transform: uppercase;
            color: #9d5bc7;
        }

        /* ── Animated underline link ── */
        .link-underline-accent {
            background-image: linear-gradient(#7c3ca0, #7c3ca0);
            background-size: 0% 1.5px;
            background-repeat: no-repeat;
            background-position: 0 100%;
            transition: background-size 0.35s ease, color 0.2s;
            padding-bottom: 1px;
        }
        .link-underline-accent:hover { background-size: 100% 1.5px; color: #7c3ca0; }

        /* ── How-it-works card hover line ── */
        .step-line {
            width: 2rem; height: 1px; background: rgba(255,255,255,0.12);
            transition: width 0.4s ease, background 0.4s ease;
        }
        .group:hover .step-line { width: 3.5rem; background: #7c3ca0; }

        /* ── Glow on CTA button ── */
        .cta-btn {
            animation: btn-glow 3s ease-in-out infinite;
        }

        /* ── Hero product images responsive ── */
        .hero-images { min-height: 240px; }
        @media (min-width: 1024px) { .hero-images { min-height: 520px; } }
        .hero-img-back  { width: 110px; height: 130px; }
        .hero-img-front { width: 125px; height: 150px; }
        .hero-img-sm    { width:  90px; height: 108px; }
        .hero-img-xs    { width:  76px; height:  92px; }
        @media (min-width: 1024px) {
            .hero-img-back  { width: 220px; height: 260px; }
            .hero-img-front { width: 250px; height: 300px; }
            .hero-img-sm    { width: 175px; height: 210px; }
            .hero-img-xs    { width: 150px; height: 180px; }
        }

        /* ── Design gallery scroll ── */
        @keyframes scroll-left  { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        @keyframes scroll-right { from { transform: translateX(-50%); } to { transform: translateX(0); } }
        @keyframes gallery-shimmer { 0%,100% { opacity:0.5; } 50% { opacity:1; } }
        .gallery-track-left  { animation: scroll-left  28s linear infinite; }
        .gallery-track-right { animation: scroll-right 32s linear infinite; }
        .gallery-track-left:hover,
        .gallery-track-right:hover { animation-play-state: paused; }
        /* Placeholder tile: gradient background, always visible */
        .gallery-tile {
            background: linear-gradient(135deg, rgba(124,60,160,0.12) 0%, rgba(26,26,26,0.7) 50%, rgba(90,34,117,0.1) 100%);
            border: 1px solid rgba(124,60,160,0.15);
        }
        .gallery-tile::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(124,60,160,0.08), transparent 60%);
            animation: gallery-shimmer 3s ease-in-out infinite;
        }

        .factory-grid-light {
            background-image:
                linear-gradient(rgba(26,26,26,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(26,26,26,0.05) 1px, transparent 1px);
            background-size: 72px 72px;
        }
        /* Corner bracket marks */
        .corner-marks::before, .corner-marks::after {
            content: '';
            position: absolute;
    </style>
</head>

<body class="bg-ink text-ink font-sans antialiased overflow-x-hidden grain">
@php $navDarkHero = true; @endphp
@include('layouts.navigation')

<!-- ════════════════════ HERO ════════════════════ -->
<section class="relative min-h-screen flex flex-col justify-center overflow-hidden bg-ink corner-marks">

    <!-- Background video -->
    <video autoplay loop muted playsinline
           class="absolute inset-0 w-full h-full object-cover opacity-[0.07]">
        <source src="/videos/video-fondo-prueba.mp4" type="video/mp4">
    </video>

    <!-- Industrial grid -->
    <div class="absolute inset-0 factory-grid"></div>

    <!-- Floating orbs -->
    <div class="orb orb-a" style="width:700px;height:700px;top:-180px;right:-150px;animation:float-a 10s ease-in-out infinite;"></div>
    <div class="orb orb-b" style="width:450px;height:450px;bottom:-60px;left:-100px;animation:float-b 13s ease-in-out infinite 2s;"></div>

    <!-- Scan line -->
    <div class="scan-line"></div>

    <!-- Extra corner accents -->
    <div class="absolute top-8 right-8 w-6 h-6 border-t-2 border-r-2 hidden sm:block" style="border-color:rgba(124,60,160,0.4)"></div>
    <div class="absolute bottom-8 left-8 w-6 h-6 border-b-2 border-l-2 hidden sm:block" style="border-color:rgba(124,60,160,0.4)"></div>

    <!-- Hero content -->
    <div
        x-data="{ show: false }"
        x-init="setTimeout(() => show = true, 80)"
        :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
        class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 pt-24 pb-8 sm:pt-36 sm:pb-24 transition-all duration-1000 ease-out"
    >
        <div class="grid lg:grid-cols-2 gap-6 lg:gap-20 items-center">

            <!-- Left: copy -->
            <div>
               
              

                <!-- Headline -->
                <h1 class="font-serif leading-[0.87] mb-6 sm:mb-14"
                    style="font-size:clamp(3rem,8vw,8rem)">
                    <span style="color:rgba(255,255,255,0.82)">Ready to sell.</span><br>
                    <span class="italic gradient-text">Products</span><br>
                    <span style="color:rgba(255,255,255,0.82)">in seconds.</span>
                </h1>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5 sm:gap-10">
                    <a href="/design"
                       class="btn-shimmer inline-flex items-center gap-3 px-8 py-4 bg-accent text-white
                              text-xs font-semibold tracking-[0.2em] uppercase
                              hover:bg-accent-dark hover:-translate-y-0.5 transition-all duration-300">
                        Start Your Brand <span>→</span>
                    </a>
                    <a href="#how-it-works"
                       class="inline-flex items-center gap-3 text-xs font-medium tracking-[0.2em] uppercase
                              text-white/40 hover:text-white transition-colors duration-300">
                        How it works <span class="opacity-50">↓</span>
                    </a>
                </div>

                <!-- Stats -->
                <div
                    x-data="{ visible: false }"
                    x-intersect.once="visible = true"
                    class="mt-8 pt-5 sm:mt-16 sm:pt-8 border-t border-white/[0.07] grid grid-cols-3 gap-4 sm:gap-6 max-w-sm"
                >
                    @php
                    $count = $totalDesigns ?? 0;
                    $countFormatted = $count >= 1000 ? number_format($count / 1000, 1) . 'K+' : ($count > 0 ? number_format($count) . '+' : '0');
                    $stats = [
                        ['val' => $countFormatted, 'label' => 'Designs generated',    'delay' => 'stat-d1'],
                        ['val' => '✓',             'label' => 'Auto-upload to store', 'delay' => 'stat-d2'],
                        ['val' => '20+',           'label' => 'Garments available',   'delay' => 'stat-d3'],
                    ]; @endphp
                    @foreach($stats as $s)
                    <div>
                        <p class="font-serif text-3xl text-white {{ $s['delay'] }}"
                           :class="visible ? 'stat-num' : 'opacity-0'">{{ $s['val'] }}</p>
                        <p class="text-[10px] text-white/30 tracking-[0.2em] uppercase mt-1">{{ $s['label'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Right: product showcase -->
            <div class="flex items-center justify-center relative w-full mt-8 lg:mt-0 hero-images">
                <!-- Purple glow behind images -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div style="width:380px;height:380px;background:radial-gradient(circle,rgba(124,60,160,0.22) 0%,transparent 70%);filter:blur(40px)"></div>
                </div>
                <!-- Image 1: front-left, largest -->
                <div class="absolute" style="left:0;top:5%;animation:float-a 9s ease-in-out infinite">
                    <div class="hero-img-front" style="background:rgba(255,255,255,0.04);border:1px solid rgba(124,60,160,0.3);border-radius:16px;overflow:hidden;box-shadow:0 40px 80px -15px rgba(0,0,0,0.8)">
                        <img src="{{ asset('images/gallery/d1.png') }}"
                             alt="AI designed product"
                             class="w-full h-full object-contain p-3"
                             onerror="this.parentElement.style.opacity='0'">
                    </div>
                </div>
                <!-- Image 2: back-right, medium -->
                <div class="absolute" style="right:0;top:8%;animation:float-b 11s ease-in-out infinite 1s">
                    <div class="hero-img-back" style="background:rgba(255,255,255,0.03);border:1px solid rgba(124,60,160,0.2);border-radius:16px;overflow:hidden;box-shadow:0 30px 70px -15px rgba(0,0,0,0.7)">
                        <img src="{{ asset('images/gallery/d2.png') }}"
                             alt="AI designed product"
                             class="w-full h-full object-contain p-3"
                             onerror="this.parentElement.style.opacity='0'">
                    </div>
                </div>
                <!-- Image 3: lower-left, smaller -->
                <div class="absolute" style="left:8%;bottom:4%;animation:float-b 13s ease-in-out infinite 0.5s">
                    <div class="hero-img-sm" style="background:rgba(255,255,255,0.03);border:1px solid rgba(124,60,160,0.18);border-radius:14px;overflow:hidden;box-shadow:0 20px 50px -10px rgba(0,0,0,0.6)">
                        <img src="{{ asset('images/gallery/d3.png') }}"
                             alt="AI designed product"
                             class="w-full h-full object-contain p-2"
                             onerror="this.parentElement.style.opacity='0'">
                    </div>
                </div>
                <!-- Image 4: lower-right, smallest -->
                <div class="absolute" style="right:4%;bottom:2%;animation:float-a 10s ease-in-out infinite 2s">
                    <div class="hero-img-xs" style="background:rgba(255,255,255,0.03);border:1px solid rgba(124,60,160,0.15);border-radius:12px;overflow:hidden;box-shadow:0 16px 40px -8px rgba(0,0,0,0.6)">
                        <img src="{{ asset('images/gallery/d4.png') }}"
                             alt="AI designed product"
                             class="w-full h-full object-contain p-2"
                             onerror="this.parentElement.style.opacity='0'">
                    </div>
                </div>
            </div>

        </div>

        <!-- Trusted by strip — visible in first screen -->
        <div class="mt-6 pt-5 sm:mt-12 sm:pt-8 border-t border-white/[0.07]">
            <p class="text-center text-[9px] font-semibold tracking-[0.3em] uppercase mb-7" style="color:rgba(255,255,255,0.22)">Trusted by creators selling on</p>
            <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-4 sm:gap-x-10 sm:gap-y-5">
                <div class="flex items-center gap-3 opacity-45 hover:opacity-90 transition-opacity duration-300">
                    <i class="fab fa-shopify text-2xl" style="color:#96bf48"></i>
                    <span class="text-base font-semibold tracking-wide text-white">Shopify</span>
                </div>
                <div class="flex items-center gap-3 opacity-45 hover:opacity-90 transition-opacity duration-300">
                    <i class="fab fa-etsy text-2xl" style="color:#f56400"></i>
                    <span class="text-base font-semibold tracking-wide text-white">Etsy</span>
                </div>
                <div class="flex items-center gap-3 opacity-45 hover:opacity-90 transition-opacity duration-300">
                    <i class="fab fa-ebay text-2xl" style="color:#e53238"></i>
                    <span class="text-base font-semibold tracking-wide text-white">eBay</span>
                </div>
                <div class="flex items-center gap-3 opacity-45 hover:opacity-90 transition-opacity duration-300">
                    <i class="fab fa-amazon text-2xl" style="color:#ff9900"></i>
                    <span class="text-base font-semibold tracking-wide text-white">Amazon Merch</span>
                </div>
                <div class="flex items-center gap-3 opacity-45 hover:opacity-90 transition-opacity duration-300">
                    <i class="fas fa-store text-xl text-white/60"></i>
                    <span class="text-base font-semibold tracking-wide text-white">WooCommerce</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════ MARQUEE ════════════════════ -->
<section class="border-y border-white/[0.07] py-4 overflow-hidden" style="background:#161616">
    <div class="marquee-track">
        @for($i = 0; $i < 2; $i++)
        <span class="flex items-center gap-7 mr-7">
            @php $items = ['AI Clothing Design','Print on Demand','T-Shirt Design','Printify Ready','POD Automation','Dropshipping']; @endphp
            @foreach($items as $item)
            <span class="text-[10px] font-semibold tracking-[0.3em] uppercase" style="color:rgba(255,255,255,0.35)">{{ $item }}</span>
            <span class="w-1.5 h-1.5 rounded-full shrink-0 bg-accent"></span>
            @endforeach
        </span>
        @endfor
    </div>
</section>

<!-- ════════════════════ STATEMENT ════════════════════ -->
<section class="bg-ink factory-grid py-24 md:py-40 overflow-hidden">
    <div class="max-w-7xl mx-auto px-6">

        <div class="grid lg:grid-cols-[1fr_360px] gap-8 lg:gap-16 items-end mb-10 lg:mb-20">
            <div
                x-data="{ show: false }"
                x-intersect.once="show = true"
                :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'"
                class="transition-all duration-700 ease-out"
            >
                <h2 class="font-serif leading-[0.9]" style="color:rgba(255,255,255,0.82);font-size:clamp(3rem,7vw,7rem)">
                    AI fashion design<br>
                    <span class="italic gradient-text">for ecommerce</span><br>
                    at full speed.
                </h2>
            </div>
            <div
                x-data="{ show: false }"
                x-intersect.once="show = true"
                :class="show ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'"
                class="transition-all duration-700 ease-out delay-200"
            >
                <div class="w-8 h-px bg-accent mb-6"></div>
                <p class="text-white/40 leading-relaxed text-base">
                    Generate apparel graphics, publish to Printify, and launch new products fast.
                    AI design, print on demand, and ecommerce automation in one workflow.
                </p>
                <a href="/design"
                   class="link-underline-accent inline-flex items-center gap-2 mt-8 text-xs font-semibold
                          tracking-[0.2em] uppercase text-white/60 hover:text-white transition-colors duration-300">
                    Start building <span>→</span>
                </a>
            </div>
        </div>

        <!-- Pain-point tiles -->
        <div class="grid sm:grid-cols-3 gap-px bg-white/[0.04]">
            @php
            $problems = [
                ['icon' => 'fa-clock-rotate-left', 'label' => 'Slow product creation?',  'desc' => 'From prompt to print-ready design in seconds.'],
                ['icon' => 'fa-user-slash',         'label' => 'No in-house designer?',   'desc' => 'Use AI to create professional t-shirt and apparel graphics.'],
                ['icon' => 'fa-hourglass-half',     'label' => 'Need faster drops?',      'desc' => 'Launch more POD products with less manual work.'],
            ];
            @endphp
            @foreach($problems as $i => $p)
            <div
                x-data="{ show: false }"
                x-intersect.once="show = true"
                :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                class="bg-[#111] p-8 group card-lift stagger-{{ $i+1 }} transition-all duration-700"
            >
                <div class="w-10 h-10 bg-ink group-hover:bg-accent flex items-center justify-center mb-5 transition-colors duration-300">
                    <i class="fas {{ $p['icon'] }} text-xs text-white icon-bounce"></i>
                </div>
                <h3 class="text-xs font-bold text-white/70 uppercase tracking-[0.12em] mb-2">{{ $p['label'] }}</h3>
                <p class="text-sm text-white/35 leading-relaxed">{{ $p['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ════════════════════ HOW IT WORKS (gallery removed) ════════════════════ -->
<section style="display:none"></section>
<section class="py-16 md:py-24 overflow-hidden" style="display:none">
    <div class="max-w-7xl mx-auto px-6 mb-10">
        <div
            x-data="{ show: false }"
            x-intersect.once="show = true"
            :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
            class="transition-all duration-700 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4"
        >
            <div>
                <p class="text-[10px] font-medium tracking-[0.35em] uppercase text-accent mb-3">Generated with FabricAI</p>
                <h2 class="font-serif text-3xl sm:text-4xl" style="color:rgba(255,255,255,0.82)">Real designs. <span class="italic gradient-text">Real products.</span></h2>
            </div>
            <a href="/design" class="text-[10px] font-semibold tracking-[0.2em] uppercase text-white/30 hover:text-accent transition-colors shrink-0">Create yours →</a>
        </div>
    </div>

    {{-- Row 1: scrolls left --}}
    <div class="gallery-track-left flex gap-4 mb-4" style="width:max-content">
        @php $row1 = ['d1','d2','d3','d4','d5','d6','d7','d8']; @endphp
        @foreach(array_merge($row1,$row1) as $img)
        <div class="shrink-0 rounded-xl overflow-hidden relative gallery-tile" style="width:180px;height:180px;">
            <img src="{{ asset('images/gallery/'.$img.'.png') }}"
                 alt="AI clothing design example"
                 class="w-full h-full object-cover relative z-10"
                 loading="lazy"
                 onerror="this.style.display='none'">
        </div>
        @endforeach
    </div>

    {{-- Row 2: scrolls right --}}
    <div class="gallery-track-right flex gap-4" style="width:max-content">
        @php $row2 = ['d5','d6','d7','d8','d1','d2','d3','d4']; @endphp
        @foreach(array_merge($row2,$row2) as $img)
        <div class="shrink-0 rounded-xl overflow-hidden relative gallery-tile" style="width:180px;height:180px;">
            <img src="{{ asset('images/gallery/'.$img.'.png') }}"
                 alt="AI clothing design example"
                 class="w-full h-full object-cover relative z-10"
                 loading="lazy"
                 onerror="this.style.display='none'">
        </div>
        @endforeach
    </div>
</section>

<!-- ════════════════════ HOW IT WORKS (after gallery removed) ════════════════════ -->
<section id="how-it-works" class="bg-ink section-padding relative overflow-hidden">
    <div class="absolute inset-0 factory-grid"></div>
    <div class="orb orb-a absolute" style="width:500px;height:500px;top:-80px;left:-100px;opacity:0.7;animation:float-b 12s ease-in-out infinite;"></div>
    <div class="scan-line" style="animation-delay:4s"></div>

    <div class="relative max-w-7xl mx-auto px-6">
        <div class="mb-10 sm:mb-20 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 sm:gap-8">
            <div
                x-data="{ show: false }"
                x-intersect.once="show = true"
                :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
                class="transition-all duration-700"
            >
                <p class="text-[10px] font-medium tracking-[0.35em] uppercase text-accent mb-4">Process</p>
                <h2 class="font-serif text-4xl sm:text-5xl lg:text-6xl leading-tight" style="color:rgba(255,255,255,0.82)">
                    From prompt to<br><span class="italic gradient-text">POD product.</span>
                </h2>
            </div>
            <p class="text-white/30 text-sm leading-relaxed sm:text-right" style="max-width:240px">
                AI generation, background removal,
                Printify sync, instant publishing.
            </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-px bg-white/[0.06]">
            @php
            $steps = [
                ['num' => '01', 'title' => 'Write a prompt',      'desc' => 'Describe the graphic or decoration you want — motif, style, colors, mood.'],
                ['num' => '02', 'title' => 'Generate with AI',    'desc' => 'Get product-ready graphics for print on demand.'],
                ['num' => '03', 'title' => 'Clean and refine',    'desc' => 'Remove backgrounds and iterate variations instantly.'],
                ['num' => '04', 'title' => 'Publish to Printify', 'desc' => 'Sync products and launch your ecommerce catalog.'],
            ];
            @endphp
            @foreach($steps as $i => $step)
            <div
                x-data="{ show: false }"
                x-intersect.once="show = true"
                :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
                class="bg-ink p-10 transition-all duration-700 group hover:bg-white/[0.04] cursor-default stagger-{{ $i+1 }}"
            >
                <span class="font-serif text-5xl text-white/[0.1] group-hover:text-accent/60 transition-colors duration-500">
                    {{ $step['num'] }}
                </span>
                <div class="step-line mt-6 mb-4"></div>
                <h3 class="text-xs font-bold text-white uppercase tracking-[0.12em] mb-3">{{ $step['title'] }}</h3>
                <p class="text-sm text-white/35 leading-relaxed">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ════════════════════ FEATURES ════════════════════ -->
<section class="bg-[#0f0f0f] section-padding" style="border-top:1px solid rgba(255,255,255,0.06)">
    <div class="max-w-7xl mx-auto px-6">
        <div
            x-data="{ show: false }"
            x-intersect.once="show = true"
            :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
            class="transition-all duration-700 mb-16"
        >
            <p class="text-[10px] font-medium tracking-[0.35em] uppercase text-accent mb-4">What you get</p>
            <h2 class="font-serif text-4xl sm:text-5xl" style="color:rgba(255,255,255,0.82)">Built for AI ecommerce.</h2>
            <div class="w-14 h-px bg-accent mt-6"></div>
        </div>

        @php
        $features = [
            ['icon' => 'fa-bolt',        'title' => 'AI design generator', 'desc' => 'Fast image generation for apparel, streetwear, and t-shirt design.'],
            ['icon' => 'fa-eraser',      'title' => 'Background removal',  'desc' => 'Instant transparent PNG for clean print on demand production.'],
            ['icon' => 'fa-store',       'title' => 'Printify integration', 'desc' => 'Send new designs to your catalog and publish products in one flow.'],
            ['icon' => 'fa-layer-group', 'title' => 'Design library',      'desc' => 'Save, reuse, and scale high-performing designs across collections.'],
        ];
        @endphp
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-px bg-white/[0.04]">
            @foreach($features as $i => $f)
            <div
                x-data="{ show: false }"
                x-intersect.once="show = true"
                :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                class="bg-[#111] p-8 group card-lift stagger-{{ $i+1 }} transition-all duration-700"
            >
                <div class="w-10 h-10 bg-white/[0.06] border border-white/10
                            group-hover:bg-accent group-hover:border-accent
                            flex items-center justify-center mb-6 transition-all duration-300">
                    <i class="fas {{ $f['icon'] }} text-sm text-white/40 group-hover:text-white icon-bounce transition-colors duration-300"></i>
                </div>
                <h3 class="text-xs font-bold text-white/70 uppercase tracking-[0.12em] mb-2">{{ $f['title'] }}</h3>
                <p class="text-sm text-white/35 leading-relaxed">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ════════════════════ DEMO TYPING ════════════════════ -->
<section class="bg-ink border-y border-white/[0.06] factory-grid py-20 md:py-28">
    <div class="max-w-6xl mx-auto px-6">
        <div class="grid lg:grid-cols-2 gap-16 items-center">

            <!-- Copy -->
            <div
                x-data="{ show: false }"
                x-intersect.once="show = true"
                :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                class="transition-all duration-700"
            >
                <p class="text-[10px] font-medium tracking-[0.35em] uppercase text-accent mb-4">See it in action</p>
                <h2 class="font-serif text-4xl sm:text-5xl leading-tight mb-6" style="color:rgba(255,255,255,0.82)">
                    Type it.<br><span class="italic gradient-text">Sell it.</span>
                </h2>
                <p class="text-white/40 text-base leading-relaxed mb-8">
                    AI t-shirt design, hoodie graphics, and apparel artwork generated in seconds.
                    Ready for print on demand and dropshipping stores.
                </p>
                <a href="/design"
                   class="btn-shimmer inline-flex items-center gap-3 px-8 py-4 bg-accent text-white
                          text-xs font-semibold tracking-[0.2em] uppercase
                          hover:bg-accent-dark hover:-translate-y-0.5 transition-all duration-300">
                    Launch Studio →
                </a>
            </div>

            <!-- Typing animation card -->
            <div
                x-data="{
                    show: false,
                    examples: [
                        'A playful pixel art robot eating a pink frosted donut with colorful sprinkles',
                        'Japanese streetwear aesthetic with bright neon lights and vibrant Tokyo nightlife energy',
                        'Minimalistic geometric pattern made of repeating shapes and sharp symmetrical lines',
                        'Surreal dreamlike landscape with floating elements and soft gradients',
                    ],
                    idx: 0, prompt: '', t: null,
                    type() {
                        clearTimeout(this.t);
                        const p = this.examples[this.idx]; let i = 0;
                        const go = () => {
                            if (i <= p.length) { this.prompt = p.slice(0, i++); this.t = setTimeout(go, 52); }
                            else { this.t = setTimeout(() => this.erase(), 2600); }
                        }; go();
                    },
                    erase() {
                        clearTimeout(this.t);
                        let i = this.prompt.length;
                        const go = () => {
                            if (i >= 0) { this.prompt = this.prompt.slice(0, i--); this.t = setTimeout(go, 24); }
                            else { this.idx = (this.idx + 1) % this.examples.length; this.type(); }
                        }; go();
                    },
                    init() { setTimeout(() => this.show = true, 200); this.type(); }
                }"
                x-init="init()"
                :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                class="transition-all duration-700 delay-200"
            >
                <div class="bg-[#111] border border-white/[0.08] p-6"
                     style="box-shadow:0 24px 60px -12px rgba(124,60,160,0.14)">
                    <div class="flex items-center gap-2.5 mb-4 pb-4 border-b border-white/[0.07]">
                        <span class="pulse-dot w-2 h-2 rounded-full inline-block bg-accent"></span>
                        <span class="text-[10px] font-semibold text-white/30 uppercase tracking-[0.25em]">FabricAI Studio</span>
                        <span class="ml-auto text-[10px] px-2.5 py-0.5 rounded-full text-white font-semibold tracking-wider bg-accent">LIVE</span>
                    </div>
                    <div class="min-h-[72px] text-white/60 text-sm leading-relaxed">
                        <span x-text="prompt"></span><span class="inline-block w-0.5 h-4 bg-accent animate-pulse align-middle ml-0.5"></span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-white/[0.07] flex justify-between items-center">
                        <span class="text-[10px] text-white/30 tracking-widest uppercase flex items-center gap-1.5">
                            <span class="w-1 h-1 rounded-full bg-accent inline-block" style="animation:pulse-ring 1.8s ease-out infinite"></span>
                            Generating…
                        </span>
                        <span class="inline-block px-4 py-2 bg-accent text-white text-[10px] font-semibold tracking-[0.2em] uppercase">Generate →</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ════════════════════ CTA ════════════════════ -->
<section class="bg-ink section-padding relative overflow-hidden corner-marks">
    <div class="absolute inset-0 factory-grid"></div>
    <div class="orb orb-a absolute" style="width:800px;height:800px;top:-260px;right:-250px;opacity:0.55;animation:float-a 14s ease-in-out infinite;"></div>
    <div class="scan-line" style="animation-delay:2s"></div>

    <!-- Ghost text -->
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none overflow-hidden">
        <span class="font-serif text-[22vw] leading-none whitespace-nowrap"
              style="color:rgba(124,60,160,0.04)">GENERATE</span>
    </div>

    <div class="relative max-w-4xl mx-auto px-6 text-center">
        <div class="w-10 h-px bg-accent mx-auto mb-10"></div>
        <h2
            x-data="{ show: false }"
            x-intersect.once="show = true"
            :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
            class="font-serif text-5xl sm:text-6xl lg:text-7xl leading-[0.92] mb-8 transition-all duration-700"
            style="color:rgba(255,255,255,0.82)"
        >
            Less editing.<br>
            <span class="italic gradient-text">More selling.</span>
        </h2>
        <p class="text-white/35 text-lg max-w-md mx-auto mb-12 leading-relaxed">
            AI design generator for print on demand.<br>
            Launch faster and scale your apparel brand.
        </p>
        <a href="/design"
           class="btn-shimmer cta-btn relative inline-flex items-center gap-3 px-10 py-4 bg-accent text-white
                  text-xs font-semibold tracking-[0.2em] uppercase
                  hover:bg-accent-dark hover:-translate-y-1 transition-all duration-300">
            Generate your first design <span>→</span>
        </a>
    </div>
</section>

@include('layouts.footer')

</body>
</html>