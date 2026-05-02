<!DOCTYPE html>
<html lang="en" style="background:#0d0d0d">
<head>
    <link rel="icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FAQ — FabricAI</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
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
        .faq-item {
            border-bottom: 1px solid rgba(255,255,255,0.06);
            transition: background 0.2s;
        }
        .faq-item:last-child { border-bottom: none; }
        .faq-item:hover { background: rgba(255,255,255,0.025); }
    </style>
</head>
<body class="bg-[#0d0d0d] text-white font-sans antialiased overflow-x-hidden">
@php $navDarkHero = true; @endphp
@include('layouts.navigation')

<!-- HERO -->
<section class="relative pt-20 pb-20 text-center overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="orb orb-a" style="width:600px;height:600px;top:-180px;right:-80px;animation:float-a 10s ease-in-out infinite;"></div>
    <div class="orb orb-b" style="width:400px;height:400px;bottom:0;left:-80px;animation:float-b 13s ease-in-out infinite 2s;"></div>
    <div class="scan-line"></div>
    <div class="absolute top-8 right-8 w-6 h-6 border-t-2 border-r-2 hidden sm:block" style="border-color:rgba(124,60,160,0.4)"></div>
    <div class="absolute top-8 left-8 w-6 h-6 border-t-2 border-l-2 hidden sm:block" style="border-color:rgba(124,60,160,0.4)"></div>
    <div x-data="{ show: false }" x-init="setTimeout(() => show = true, 80)"
         :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
         class="relative z-10 max-w-3xl mx-auto px-6 transition-all duration-1000 ease-out">
        <div class="mb-8">
            <span class="tag-pill">
                <span class="pulse-dot w-1.5 h-1.5 rounded-full inline-block" style="background:#9d5bc7"></span>
                Support
            </span>
        </div>
        <h1 class="font-serif leading-tight mb-6" style="font-size:clamp(2.8rem,7vw,6rem)">
            <span class="text-white">Frequently</span><br>
            <span class="italic gradient-text">asked</span><br>
            <span class="text-white">questions.</span>
        </h1>
        <p class="text-white/50 text-lg max-w-xl mx-auto">
            Everything you need to know about FabricAI and how it works.
        </p>
    </div>
</section>

<!-- FAQ ACCORDION -->
<section class="relative pb-24 overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="relative z-10 max-w-3xl mx-auto px-6">
        <div class="rounded-2xl overflow-hidden" style="border:1px solid rgba(255,255,255,0.07);background:#111;">
            @php
            $faqs = [
                [
                    'q' => 'Why use FabricAI instead of ChatGPT or Gemini?',
                    'a' => 'Unlike broader AI models, FabricAI is entirely designed to create suitable designs for clothing — clean, visible, and attractive images with adapted backgrounds that fit any type of garment. FabricAI is highly trained on the Printify print-on-demand catalog, generating images with the perfect size and quality Printify requires.'
                ],
                [
                    'q' => 'Is FabricAI a mockup generator?',
                    'a' => 'No. Unlike mockup tools, FabricAI creates the product design itself — not a guide image. The designs generated are finished products ready for selling.'
                ],
                [
                    'q' => 'What do I do with my designs?',
                    'a' => 'FabricAI creates fully designed models ready to sell via the Printify API with a single prompt. You just need to publish the model to your store.'
                ],
                [
                    'q' => 'Is it legal to sell clothes with AI-generated images?',
                    'a' => 'Completely legal, as long as you don\'t sell copyrighted material. We strongly advise against generating such content. FabricAI is also tuned to never generate recognizable human faces.'
                ],
                [
                    'q' => 'How much does FabricAI cost?',
                    'a' => 'FabricAI offers various plans adapted to all kinds of users — including free credits so everyone can try it before committing to a plan.'
                ],
                [
                    'q' => 'Can I cancel my subscription at any time?',
                    'a' => 'Yes. You can upgrade, downgrade, or cancel at any time from your profile. Changes take effect at the start of your next billing cycle.'
                ],
                [
                    'q' => 'What AI models does FabricAI use?',
                    'a' => 'FabricAI uses a combination of state-of-the-art diffusion models optimised specifically for print-on-demand artwork. All plans get access to all available models.'
                ],
                [
                    'q' => 'How does the Printify integration work?',
                    'a' => 'Connect your Printify account in Settings, then use the Turbo Upload feature to push your generated design straight to your Printify catalog as a ready-to-sell product — no downloading or re-uploading required.'
                ],
            ];
            @endphp

            @foreach($faqs as $index => $faq)
            <div x-data="{ open: false }"
                 x-intersect.once="$el.classList.remove('opacity-0','translate-y-4')"
                 class="faq-item opacity-0 translate-y-4 transition-all duration-500"
                 style="transition-delay:{{ $index * 60 }}ms">
                <button @click="open = !open"
                        class="w-full flex items-center gap-4 px-7 py-5 text-left">
                    <span class="font-serif text-lg shrink-0 transition-colors duration-300"
                          :style="open ? 'color:#c084fc' : 'color:rgba(255,255,255,0.15)'">
                        {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                    </span>
                    <span class="font-medium text-white/80 text-sm sm:text-base pr-4 flex-1">{{ $faq['q'] }}</span>
                    <span :class="open ? 'rotate-45' : ''"
                          class="text-xl shrink-0 transition-all duration-300"
                          :style="open ? 'color:#c084fc' : 'color:rgba(255,255,255,0.2)'">+</span>
                </button>
                <div x-show="open" x-collapse x-cloak
                     class="px-7 pb-6 pl-[4.25rem] text-white/45 text-sm leading-relaxed">
                    {{ $faq['a'] }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA -->
<section class="relative py-28 text-center overflow-hidden">
    <div class="absolute inset-0 factory-grid pointer-events-none"></div>
    <div class="orb orb-a" style="width:500px;height:500px;bottom:-100px;left:50%;transform:translateX(-50%);"></div>
    <div class="relative z-10 max-w-xl mx-auto px-6">
        <p class="text-white/30 text-sm mb-3">Still have questions?</p>
        <h3 class="font-serif text-3xl sm:text-4xl text-white mb-4">Ready to start creating?</h3>
        <p class="text-white/40 text-sm mb-10">Jump right in — free credits included, no commitment needed.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="/pricing" class="px-8 py-3 rounded-full text-sm font-medium text-white/70 hover:text-white transition-colors" style="border:1px solid rgba(255,255,255,0.15)">View pricing</a>
            <a href="{{ route('designs.form') }}" class="px-8 py-3 rounded-full text-sm font-semibold text-white hover:opacity-90 transition-opacity" style="background:#7c3ca0">Try FabricAI free</a>
        </div>
    </div>
</section>

@include('layouts.footer')
</body>
</html>
