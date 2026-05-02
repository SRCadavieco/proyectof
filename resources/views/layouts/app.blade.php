<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name', 'FabricAI') }}</title>

        <link rel="icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" type="image/png">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen {{ $bodyClass ?? 'bg-cream-50' }}">
            @include('layouts.navigation')

            <!-- Page Content -->
            <main class="pt-20">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
