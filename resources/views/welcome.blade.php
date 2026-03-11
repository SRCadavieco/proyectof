<!DOCTYPE html>
<html lang="en">
<head>
        <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FabricAI — AI Clothing Design Platform</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html {
            scroll-behavior: smooth;
        }
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-950 text-white overflow-x-hidden">
@include('layouts.navigation')

<!-- ================= HERO ================= -->
<section class="relative h-screen flex items-center justify-center text-center overflow-hidden">

    <video autoplay loop muted playsinline
           class="absolute inset-0 w-full h-full object-cover brightness-50">
        <source src="/videos/video-fondo-prueba.mp4" type="video/mp4">
    </video>

    <!-- Particles -->
    <div id="particles-js" class="absolute inset-0 z-0"></div>

    <!-- Overlay -->
    <div class="absolute inset-0 bg-gradient-to-b from-black/50 to-black/80"></div>
    <!-- Bottom fade into page background -->
    <div class="absolute bottom-0 left-0 right-0 h-48 bg-gradient-to-t from-gray-950 to-transparent pointer-events-none"></div>

    <!-- Hero content -->
    <div 
        x-data="{
            show:false,
            promptExamples: [
                'A playful pixel art robot eating a pink frosted donut with colorful sprinkles',
                'Japanese streetwear inspired aesthetic with bright neon lights and vibrant Tokyo nightlife energy',
                'Intricate geometric pattern made of repeating AI-generated shapes and sharp symmetrical lines',
                'Surreal dreamlike landscape with floating churros drifting',
                'Dark futuristic cyberpunk aesthetic with neon lights and dystopian cityscape',
                'Minimalistic logo of a smiling algorithm made from simple geometric digital lines',
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
                        this.promptTimeout = setTimeout(type, 80);
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
                        this.promptTimeout = setTimeout(erase, 40);
                    } else {
                        this.promptIndex = (this.promptIndex + 1) % this.promptExamples.length;
                        this.typePrompt();
                    }
                };
                erase();
            },
            init() {
                setTimeout(()=>this.show=true,300);
                this.typePrompt();
            }
        }"
        x-init="init()"
        :class="show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
        class="relative z-10 px-6 transition-all duration-1000"
    >
        <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold mb-6 leading-tight">
            Start designing <span class="text-purple-400">today</span>
        </h1>

        <p class="text-gray-300 text-lg sm:text-xl max-w-2xl mx-auto mb-10">
            Instantly generate unique fashion designs powered by AI. Describe your idea and let the magic happen.
        </p>

        <a href="/design"
           class="inline-block px-8 py-4 rounded-xl font-semibold
                  bg-gradient-to-r from-purple-500 to-indigo-500
                  shadow-lg shadow-purple-500/30
                  hover:scale-105 transition-all duration-300">
            Start your design ✨
        </a>

        <div class="max-w-xl mx-auto mt-8">
            <div class="w-full h-20 bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-gray-200 font-mono text-base opacity-70 text-left leading-relaxed overflow-hidden">
                <span x-text="prompt"></span><span class="inline-block w-0.5 h-5 bg-purple-400 animate-pulse align-middle ml-0.5"></span>
            </div>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function () {
            particlesJS("particles-js", {
                particles: {
                    number: {
                        value: 70,
                        density: { enable: true, value_area: 900 }
                    },
                    color: {
                        value: "#7004F5"
                    },
                    shape: {
                        type: "circle"
                    },
                    opacity: {
                        value: 0.6,
                        random: true
                    },
                    size: {
                        value: 3,
                        random: true
                    },
                    line_linked: {
                        enable: true,
                        distance: 150,
                        color: "#7004F5",
                        opacity: 0.4,
                        width: 1
                    },
                    move: {
                        enable: true,
                        speed: 1.5,
                        direction: "none",
                        random: false,
                        straight: false,
                        out_mode: "out",
                        bounce: false
                    }
                },
                interactivity: {
                    detect_on: "canvas",
                    events: {
                        onhover: {
                            enable: true,
                            mode: "repulse"
                        },
                        onclick: {
                            enable: true,
                            mode: "push"
                        }
                    },
                    modes: {
                        repulse: {
                            distance: 120,
                            duration: 0.4
                        },
                        push: {
                            particles_nb: 4
                        }
                    }
                },
                retina_detect: true
            });
        });
        </script>
        </div>
    </div>

</section>

<!-- ================= HOW IT WORKS ================= -->
<section id="how-it-works" class="relative py-32 bg-gray-950">

    <!-- Background glow -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 
                w-[800px] h-[800px] bg-purple-600/10 blur-[120px] rounded-full">
    </div>

    <div class="relative max-w-6xl mx-auto px-6">

        <h2 class="text-4xl sm:text-5xl font-bold text-center mb-24
                   bg-gradient-to-r from-purple-400 via-indigo-400 to-purple-500
                   bg-clip-text text-transparent">
            How It Works
        </h2>

        @php
$steps = [
    [
        'title' => 'Describe your idea',
        'desc' => 'Write a detailed description of the clothing design you imagine.'
    ],
    [
        'title' => 'AI generates the design ',
        'desc' => 'Our AI model turns your prompt into a unique visual concept.'
    ],
    [
        'title' => 'Refine & iterate',
        'desc' => 'Improve the result with additional prompts and creative direction.'
    ],
    [
        'title' => 'Download & use',
        'desc' => 'Export your final design in high quality, ready for production.'
    ],
];
        @endphp

        <div class="space-y-32">

            @foreach($steps as $index => $step)
            <div 
                x-data="{ show:false }"
                x-intersect.once="show=true"
                :class="show 
                    ? 'opacity-100 translate-y-0' 
                    : 'opacity-0 translate-y-16'"
                class="flex flex-col lg:flex-row 
                       {{ $index % 2 ? 'lg:flex-row-reverse' : '' }}
                       items-center gap-16 transition-all duration-1000 ease-out"
            >

                <!-- Text -->
                <div class="flex-1 space-y-6">
                    <div class="text-purple-400 font-semibold tracking-wide">
                        Step {{ $index + 1 }}
                    </div>

                    <h3 class="text-3xl sm:text-4xl font-bold">
                        {{ $step['title'] }}
                    </h3>

                    <p class="text-gray-400 text-lg leading-relaxed max-w-lg">
                        {{ $step['desc'] }}
                    </p>
                </div>

                <!-- Visual placeholder -->
                <div class="flex-1">
                    <div class="relative group">
                        <div class="absolute -inset-2 bg-gradient-to-r 
                                    from-purple-500 to-indigo-500
                                    rounded-3xl blur-xl opacity-30
                                    group-hover:opacity-60 transition duration-500">
                        </div>

                        <div class="relative h-80 bg-gray-900 border border-gray-800 rounded-3xl
                                    flex items-center justify-center text-gray-600">
                            Visual Preview
                        </div>
                    </div>
                </div>

            </div>
            @endforeach

        </div>
    </div>
</section>



@include('layouts.footer')

</body>
</html>
