<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FabricAI') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-cream-50 text-ink">
        <div class="min-h-screen flex">

            <!-- Left decorative panel (hidden on mobile) -->
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden items-center justify-center bg-ink">
                <div class="relative z-10 text-center px-16">
                    <a href="/">
                        <img src="/images/logo.png" alt="FabricAI" class="h-20 w-20 mx-auto mb-10">
                    </a>
                    <h2 class="font-serif text-4xl text-white mb-4 leading-tight">
                        Design the future<br>
                        <span class="italic text-purple-300">with intention</span>
                    </h2>
                    <p class="text-white/50 text-lg max-w-sm mx-auto">
                        Describe your vision and watch it come to life as a unique fashion design.
                    </p>
                    <!-- Decorative line -->
                    <div class="mt-12 w-16 h-px bg-white/20 mx-auto"></div>
                </div>
            </div>

            <!-- Right form panel -->
            <div class="w-full lg:w-1/2 flex flex-col items-center justify-center px-6 py-12">
                <!-- Mobile logo -->
                <div class="lg:hidden mb-8">
                    <a href="/" class="flex items-center gap-3">
                        <img src="/images/logo.png" alt="FabricAI" class="h-12 w-12">
                        <span class="font-serif text-xl text-ink">FabricAI</span>
                    </a>
                </div>

                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>

                <p class="mt-10 text-xs text-ink-muted">
                    &copy; {{ date('Y') }} FabricAI. All rights reserved.
                </p>
            </div>
        </div>
    </body>
</html>
