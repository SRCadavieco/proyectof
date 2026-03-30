<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Active — FabricAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-cream-50 text-ink overflow-x-hidden">

@include('layouts.navigation')

<section class="relative pt-36 pb-20 text-center min-h-screen">
    <div class="relative max-w-xl mx-auto px-6">
        <!-- Success icon -->
        <div class="mx-auto w-20 h-20 rounded-full bg-green-50 border border-green-200 flex items-center justify-center mb-8">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="font-serif text-4xl sm:text-5xl leading-tight mb-4">
            You're all set!
        </h1>

        <p class="text-ink-muted text-lg mb-4">
            Your subscription is now active. Your tokens have been updated.
        </p>

        <p class="text-ink-muted text-sm mb-10">
            You can manage your billing and invoices anytime from the billing portal.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('designs.form') }}"
               class="btn-primary inline-block px-8 py-3 text-sm">
                Start designing
            </a>
            <a href="{{ route('billing') }}"
               class="btn-outline inline-block px-8 py-3 text-sm">
                Manage billing
            </a>
        </div>
    </div>
</section>

@include('layouts.footer')

</body>
</html>
        </div>
    </div>
</section>

@include('layouts.footer')

</body>
</html>
