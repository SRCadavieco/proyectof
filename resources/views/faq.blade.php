<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ — FabricAI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @vite(['resources/css/app.css','resources/js/app.js'])

    <style>
        html { scroll-behavior: smooth; }
        [x-cloak] { display: none !important; }
    </style>
</head>

<body class="bg-gray-950 text-white overflow-x-hidden">

<!-- ================= NAVBAR ================= -->
<nav class="fixed w-full z-50 bg-gray-950/90 backdrop-blur-md border-b border-gray-800">
    <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">
        <a href="/" class="text-2xl font-bold bg-gradient-to-r from-purple-400 to-indigo-500 bg-clip-text text-transparent">
            FABRICAI
        </a>

        <div class="flex gap-8 text-sm text-gray-300 font-medium">
            <a href="/#how-it-works" class="hover:text-purple-400 transition">How it works</a>
            <a href="/pricing" class="hover:text-purple-400 transition">Pricing</a>
            <a href="/faq" class="text-purple-400">FAQ</a>
        </div>

        <a href="{{ url('/designs/generate') }}"
           class="px-5 py-2 rounded-lg bg-gradient-to-r from-purple-500 to-indigo-500 text-sm font-semibold hover:opacity-90 transition">
            Start Designing
        </a>
    </div>
</nav>

<!-- ================= HERO ================= -->
<section class="pt-32 pb-20 text-center relative">
    <!-- Glow -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                w-[800px] h-[800px] bg-purple-600/10 blur-[140px] rounded-full">
    </div>
    <div class="relative z-10 max-w-3xl mx-auto px-6">
        <h1 class="text-5xl font-bold mb-6">
            Frequently Asked Questions
        </h1>
        <p class="text-gray-400 text-lg">
            Everything you need to know about FabricAI and how it works.
        </p>
    </div>
</section>

<!-- ================= FAQ SECTION ================= -->
<section class="pb-32 relative">
    <div class="max-w-4xl mx-auto px-6 space-y-6">
        @php
            $faqs = [
                [
                    'q' => 'Why should I use Fabric instead of alternatives like ChatGPT or Gemini?',
                    'a' => 'Unlike broader AI models, Fabric is entirely designed to create suitable designs for clothing, generating clean, visible and attractive images with adapted background to fit any type of clothes. Fabric is highly trained on Printify print-on-demand catalog, creating images with the perfect size and quality that Printify requests.'
                ],
                [
                    'q' => 'What do I do with my designs?',
                    'a' => 'Fabric creates fully designed models ready to sell with the Printify API with a single prompt, so you just need to publish the model on your store.'
                ],
                [
                    'q' => 'Is it legal to sell clothes with AI generated images?',
                    'a' => 'It is completely legal as long as you don’t sell copyrighted material. That is why we highly advice our users to not generate this type of content. Additionally, Fabric is tuned to never generate recognizable human faces.'
                ],
                [
                    'q' => 'How much does this cost?',
                    'a' => 'Fabric offers various plans adapted to all kinds of users. We also offer some free credits so everyone can try it out before looking for a plan.'
                ],
            ];
        @endphp

        @foreach($faqs as $index => $faq)
        <div 
            x-data="{ open:false }"
            x-intersect.once="$el.classList.remove('opacity-0','translate-y-8')"
            class="opacity-0 translate-y-8 transition-all duration-700
                   bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden"
        >
            <!-- Question -->
            <button 
                @click="open = !open"
                class="w-full flex justify-between items-center px-6 py-5 text-left"
            >
                <span class="font-semibold text-lg">
                    {{ $faq['q'] }}
                </span>
                <svg 
                    :class="open ? 'rotate-180 text-purple-400' : 'text-gray-400'"
                    class="w-5 h-5 transition-transform duration-300"
                    fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <!-- Answer -->
            <div 
                x-show="open"
                x-collapse
                x-cloak
                class="px-6 pb-6 text-gray-400 leading-relaxed"
            >
                {{ $faq['a'] }}
            </div>
        </div>
        @endforeach
    </div>
</section>

<!-- ================= CTA ================= -->
<section class="py-20 text-center border-t border-gray-800">
    <h2 class="text-3xl font-bold mb-6">
        Still have questions?
    </h2>
    <a href="{{ url('/designs/generate') }}"
       class="inline-block px-8 py-4 rounded-xl font-semibold
              bg-gradient-to-r from-purple-500 to-indigo-500
              shadow-lg shadow-purple-500/30
              hover:scale-105 transition-all duration-300">
        Try FabricAI Now ✨
    </a>
</section>

<!-- ================= FOOTER ================= -->
<footer class="border-t border-gray-800 py-10 text-center text-gray-500 text-sm">
    © {{ date('Y') }} FabricAI. All rights reserved.
</footer>

</body>
</html>
