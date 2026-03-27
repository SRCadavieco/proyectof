<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Cancelled — FabricAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-950 text-white overflow-x-hidden">

@include('layouts.navigation')

<section class="relative pt-36 pb-20 text-center overflow-hidden min-h-screen">
    <div class="absolute top-0 left-1/2 -translate-x-1/2
                w-[900px] h-[600px] bg-purple-600/10 blur-[140px] rounded-full pointer-events-none"></div>

    <div class="relative max-w-xl mx-auto px-6">
        <div class="mx-auto w-20 h-20 rounded-full bg-gray-800 border border-gray-700 flex items-center justify-center mb-8">
            <svg class="w-10 h-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>

        <h1 class="text-4xl sm:text-5xl font-bold leading-tight mb-4">
            Checkout cancelled
        </h1>

        <p class="text-gray-400 text-lg mb-4">
            No worries — you haven't been charged. You can try again whenever you're ready.
        </p>

        <p class="text-gray-500 text-sm mb-10">
            Still exploring? The free plan has everything you need to get started.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/pricing"
               class="inline-block px-8 py-3 rounded-xl font-semibold text-sm text-white
                      bg-gradient-to-r from-purple-500 to-indigo-500
                      shadow-lg shadow-purple-500/30
                      hover:opacity-90 hover:scale-105 transition-all duration-300">
                View plans
            </a>
            <a href="{{ route('designs.form') }}"
               class="inline-block px-8 py-3 rounded-xl border border-gray-700 text-sm font-semibold text-gray-300
                      hover:border-purple-500 hover:text-white transition-all duration-300">
                Continue with Free
            </a>
        </div>
    </div>
</section>

@include('layouts.footer')

</body>
</html>
