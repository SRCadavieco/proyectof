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
    <body class="font-sans antialiased bg-gray-950 text-white">
        <div class="min-h-screen flex">

            <!-- Left decorative panel (hidden on mobile) -->
            <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden items-center justify-center">
                <!-- Gradient background -->
                <div class="absolute inset-0 bg-gradient-to-br from-purple-900 via-indigo-900 to-gray-950"></div>
                <!-- Glow orb -->
                <div class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2
                            w-96 h-96 bg-purple-600/30 blur-[120px] rounded-full pointer-events-none"></div>
                <div class="relative z-10 text-center px-12">
                    <a href="/">
                        <img src="/images/logo.png" alt="FabricAI" class="h-24 w-24 mx-auto mb-8">
                    </a>
                    <h2 class="text-4xl font-bold mb-4 leading-tight">
                        Design the future<br>
                        <span class="text-purple-400">with AI</span>
                    </h2>
                    <p class="text-gray-300 text-lg max-w-sm mx-auto">
                        Describe your vision and watch it come to life as a unique fashion design.
                    </p>
                    <!-- Decorative dots -->
                    <div class="mt-10 flex justify-center gap-2">
                        <span class="w-2 h-2 bg-purple-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-indigo-400 rounded-full"></span>
                        <span class="w-2 h-2 bg-purple-600 rounded-full"></span>
                    </div>
                </div>
            </div>

            <!-- Right form panel -->
            <div class="w-full lg:w-1/2 flex flex-col items-center justify-center px-6 py-12">
                <!-- Mobile logo -->
                <div class="lg:hidden mb-8">
                    <a href="/">
                        <img src="/images/logo.png" alt="FabricAI" class="h-16 w-16 mx-auto">
                    </a>
                </div>

                <div class="w-full max-w-md">
                    {{ $slot }}
                </div>

                <p class="mt-8 text-sm text-gray-500">
                    &copy; {{ date('Y') }} FabricAI. All rights reserved.
                </p>
            </div>
        </div>
    </body>
</html>
