<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/images/logo.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FabricAI — AI Clothing Design Platform</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-cream-50 text-ink font-sans antialiased overflow-x-hidden grain">
@include('layouts.navigation')

<!-- ================= HERO ================= -->
<section class="relative min-h-screen flex items-center overflow-hidden">

    <!-- Background video - subtle -->
    <video autoplay loop muted playsinline
           class="absolute inset-0 w-full h-full object-cover opacity-20">
        <source src="/videos/video-fondo-prueba.mp4" type="video/mp4">
    </video>

    <!-- Soft overlay -->
    <div class="absolute inset-0 bg-cream-50/70"></div>

    <!-- Hero content -->
    <div class="relative z-10 max-w-6xl mx-auto px-6 pt-32 pb-20 grid lg:grid-cols-2 gap-16 items-center">
        
        <!-- Left: Text -->
        <div
            x-data="{ show: false }"
            x-init="setTimeout(() => show = true, 200)"
            :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            class="transition-all duration-1000 ease-out"
        >
            <p class="text-sm font-medium tracking-widest uppercase text-accent mb-6">AI-Powered Fashion Design</p>
            
            <h1 class="font-serif text-5xl sm:text-6xl lg:text-8xl leading-[0.95] mb-8">
                Design<br>
                clothes<br>
                <span class="italic text-purple-700">with intention</span>
            </h1>

            <!-- Editorial accent line -->
            <div class="w-16 h-px bg-accent mb-8"></div>

            <p class="text-ink-light text-lg leading-relaxed max-w-lg mb-10">
                Describe your vision and watch it become a unique clothing design. 
                From concept to production-ready in seconds.
            </p>

            <div class="flex flex-wrap gap-4">
                <a href="/design" class="btn-primary">
                    Start designing
                </a>
                <a href="#how-it-works" class="btn-outline">
                    Learn more
                </a>
            </div>
        </div>

        <!-- Right: Prompt preview -->
        <div
            x-data="{
                show: false,
                promptExamples: [
                    'A playful pixel art robot eating a pink frosted donut with colorful sprinkles',
                    'Japanese streetwear inspired aesthetic with bright neon lights and vibrant Tokyo nightlife energy',
                    'Minimalistic geometric pattern made of repeating shapes and sharp symmetrical lines',
                    'Surreal dreamlike landscape with floating elements and soft gradients',
                    'Abstract flowing shapes blended with soft pastel gradients and smooth color transitions',
                ],
                promptIndex: 0,
                prompt: '',
                promptTimeout: null,
                typePrompt() {
                    clearTimeout(this.promptTimeout);
                    const prompt = this.promptExamples[this.promptIndex];
                    let i = 0;
                    const type = () => {
                        if (i <= prompt.length) {
                            this.prompt = prompt.slice(0, i++);
                            this.promptTimeout = setTimeout(type, 60);
                        } else {
                            this.promptTimeout = setTimeout(() => this.erasePrompt(), 2500);
                        }
                    };
                    type();
                },
                erasePrompt() {
                    clearTimeout(this.promptTimeout);
                    let i = this.prompt.length;
                    const erase = () => {
                        if (i >= 0) {
                            this.prompt = this.prompt.slice(0, i--);
                            this.promptTimeout = setTimeout(erase, 30);
                        } else {
                            this.promptIndex = (this.promptIndex + 1) % this.promptExamples.length;
                            this.typePrompt();
                        }
                    };
                    erase();
                },
                init() {
                    setTimeout(() => this.show = true, 500);
                    this.typePrompt();
                }
            }"
            x-init="init()"
            :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'"
            class="transition-all duration-1000 delay-300 hidden lg:block"
        >
            <div class="bg-white border border-cream-300 rounded-lg p-8 shadow-sm hover:border-accent/40 transition-colors duration-500">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-2 h-2 rounded-full bg-accent"></div>
                    <span class="text-xs font-medium text-ink-muted uppercase tracking-wider">Design prompt</span>
                </div>
                <div class="min-h-[80px] text-ink-light text-base leading-relaxed">
                    <span x-text="prompt"></span><span class="inline-block w-0.5 h-5 bg-accent animate-pulse align-middle ml-0.5"></span>
                </div>
                <div class="mt-6 pt-6 border-t border-cream-200 flex justify-end">
                    <span class="inline-block px-5 py-2.5 bg-ink text-white text-xs font-medium tracking-wide uppercase">Generate →</span>
                </div>
            </div>
        </div>
    </div>

</section>

<!-- ================= MARQUEE ================= -->
<section class="border-y border-cream-300 py-5 overflow-hidden bg-white">
    <div class="marquee-track">
        @for($i = 0; $i < 2; $i++)
        <span class="flex items-center gap-8 mr-8">
            <span class="text-sm font-medium tracking-widest uppercase text-ink-muted">AI-Powered Design</span>
            <span class="w-1.5 h-1.5 rounded-full bg-accent shrink-0"></span>
            <span class="text-sm font-medium tracking-widest uppercase text-ink-muted">Print-on-Demand Ready</span>
            <span class="w-1.5 h-1.5 rounded-full bg-accent shrink-0"></span>
            <span class="text-sm font-medium tracking-widest uppercase text-ink-muted">Background Removal</span>
            <span class="w-1.5 h-1.5 rounded-full bg-accent shrink-0"></span>
            <span class="text-sm font-medium tracking-widest uppercase text-ink-muted">Printify Integration</span>
            <span class="w-1.5 h-1.5 rounded-full bg-accent shrink-0"></span>
            <span class="text-sm font-medium tracking-widest uppercase text-ink-muted">High Resolution</span>
            <span class="w-1.5 h-1.5 rounded-full bg-accent shrink-0"></span>
            <span class="text-sm font-medium tracking-widest uppercase text-ink-muted">No Design Skills Needed</span>
            <span class="w-1.5 h-1.5 rounded-full bg-accent shrink-0"></span>
        </span>
        @endfor
    </div>
</section>

<!-- ================= HOW IT WORKS ================= -->
<section id="how-it-works" class="section-padding bg-white">

    <div class="max-w-6xl mx-auto px-6">

        <div class="mb-20">
            <p class="text-sm font-medium tracking-widest uppercase text-accent mb-4">Process</p>
            <h2 class="font-serif text-4xl sm:text-5xl lg:text-6xl text-ink">
                How it works
            </h2>
            <div class="w-16 h-px bg-accent mt-6"></div>
        </div>

        @php
$steps = [
    [
        'num' => '01',
        'title' => 'Describe your idea',
        'desc' => 'Write a detailed description of the clothing design you imagine. Be as creative as you want.'
    ],
    [
        'num' => '02',
        'title' => 'AI generates the design',
        'desc' => 'Our AI model turns your prompt into a unique visual concept, optimized for production.'
    ],
    [
        'num' => '03',
        'title' => 'Refine & iterate',
        'desc' => 'Improve the result with additional prompts and creative direction until it\'s perfect.'
    ],
    [
        'num' => '04',
        'title' => 'Download & use',
        'desc' => 'Export your final design in high quality, ready for print-on-demand or production.'
    ],
];
        @endphp

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-px bg-cream-300">
            @foreach($steps as $step)
            <div
                x-data="{ show: false }"
                x-intersect.once="show = true"
                :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                class="bg-white p-10 transition-all duration-700 ease-out group card-hover"
            >
                <span class="font-serif text-5xl text-cream-400 group-hover:text-accent transition-colors duration-500">
                    {{ $step['num'] }}
                </span>

                <div class="w-8 h-px bg-cream-400 group-hover:bg-accent group-hover:w-12 transition-all duration-500 mt-6 mb-4"></div>

                <h3 class="text-lg font-semibold text-ink mb-3">
                    {{ $step['title'] }}
                </h3>

                <p class="text-ink-muted text-sm leading-relaxed">
                    {{ $step['desc'] }}
                </p>
            </div>
            @endforeach
        </div>

    </div>
</section>

<!-- ================= CTA BANNER ================= -->
<section class="bg-ink text-white section-padding relative overflow-hidden">
    <!-- Decorative large text -->
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none">
        <span class="font-serif text-[20vw] leading-none text-white/[0.03] whitespace-nowrap">FabricAI</span>
    </div>
    <div class="relative max-w-4xl mx-auto px-6 text-center">
        <div class="w-12 h-px bg-accent mx-auto mb-10"></div>
        <h2 class="font-serif text-4xl sm:text-5xl lg:text-6xl mb-6">
            Ready to <span class="italic text-accent-light">create</span>?
        </h2>
        <p class="text-white/50 text-lg max-w-xl mx-auto mb-10">
            Join thousands of designers using FabricAI to bring their fashion ideas to life.
        </p>
        <a href="/design"
           class="inline-block px-10 py-4 bg-white text-ink text-sm font-medium tracking-wide uppercase
                  hover:bg-accent hover:text-white transition-colors duration-300">
            Start your first design
        </a>
    </div>
</section>

@include('layouts.footer')

</body>
</html>
