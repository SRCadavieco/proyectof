<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Cancelled — FabricAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-cream-50 text-ink overflow-x-hidden">

@include('layouts.navigation')

<section class="relative pt-36 pb-20 text-center min-h-screen">
    <div class="relative max-w-xl mx-auto px-6">
        <div class="mx-auto w-20 h-20 rounded-full bg-cream-100 border border-cream-300 flex items-center justify-center mb-8">
            <svg class="w-10 h-10 text-ink-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>

        <h1 class="font-serif text-4xl sm:text-5xl leading-tight mb-4">
            Checkout cancelled
        </h1>

        <p class="text-ink-muted text-lg mb-4">
            No worries — you haven't been charged. You can try again whenever you're ready.
        </p>

        <p class="text-ink-muted text-sm mb-10">
            Still exploring? The free plan has everything you need to get started.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="/pricing"
               class="btn-primary inline-block px-8 py-3 text-sm">
                View plans
            </a>
            <a href="{{ route('designs.form') }}"
               class="btn-outline inline-block px-8 py-3 text-sm">
                Continue with Free
            </a>
        </div>
    </div>
</section>

@include('layouts.footer')

</body>
</html>
