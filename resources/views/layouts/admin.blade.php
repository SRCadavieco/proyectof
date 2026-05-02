<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FabricAI — @yield('title', 'Admin')</title>
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-cream-50 text-ink min-h-screen">

<div x-data="{ open: false }" class="min-h-screen">

    {{-- ── Mobile top bar ───────────────────────────────────────────── --}}
    <header class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-white border-b border-cream-200 h-14 flex items-center px-4 gap-3">
        <button @click="open = true" class="w-9 h-9 flex items-center justify-center rounded-lg text-ink-muted hover:text-ink hover:bg-cream-100 transition">
            <i class="fas fa-bars"></i>
        </button>
        <a href="/"><img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="FabricAI" class="h-7 w-7"></a>
        <span class="font-serif font-bold text-sm">FabricAI Admin</span>
    </header>

    {{-- ── Mobile overlay ───────────────────────────────────────────── --}}
    <div x-show="open"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="lg:hidden fixed inset-0 z-40 bg-ink/40 backdrop-blur-sm"
         style="display:none;"></div>

    {{-- ── Sidebar ───────────────────────────────────────────────────── --}}
    <aside class="w-64 bg-white border-r border-cream-200 flex flex-col fixed h-full z-50
                  -translate-x-full lg:translate-x-0 transition-transform duration-200"
           :class="{ 'translate-x-0': open }">

        {{-- Logo --}}
        <div class="p-6 border-b border-cream-200 flex items-center gap-3">
            <a href="/"><img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="FabricAI" class="h-12 w-12"></a>
            <div class="flex-1 min-w-0">
                <p class="font-serif font-bold text-sm">FabricAI</p>
                <p class="text-xs text-ink-muted uppercase tracking-wider">Admin Panel</p>
            </div>
            {{-- Close button (mobile only) --}}
            <button @click="open = false" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg text-ink-muted hover:text-ink hover:bg-cream-100 transition">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        {{-- Nav links --}}
        <nav class="flex-1 p-4 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
               @click="open = false"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.dashboard') ? 'bg-purple-50 text-purple-700' : 'text-ink-muted hover:text-ink hover:bg-cream-100' }}">
                <i class="fas fa-chart-pie w-5 text-center"></i>
                Dashboard
            </a>
            <a href="{{ route('admin.users') }}"
               @click="open = false"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.users') ? 'bg-purple-50 text-purple-700' : 'text-ink-muted hover:text-ink hover:bg-cream-100' }}">
                <i class="fas fa-users w-5 text-center"></i>
                Users
            </a>
            <a href="{{ route('admin.api-costs') }}"
               @click="open = false"
               class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.api-costs') ? 'bg-purple-50 text-purple-700' : 'text-ink-muted hover:text-ink hover:bg-cream-100' }}">
                <i class="fas fa-coins w-5 text-center"></i>
                API Costs
            </a>
        </nav>

        {{-- Admin user --}}
        <div class="p-4 border-t border-cream-200">
            <div class="flex items-center gap-3">
                @if(Auth::user()->avatar)
                    <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}"
                         class="w-8 h-8 rounded-full object-cover shrink-0">
                @else
                    <div class="w-8 h-8 rounded-full bg-ink flex items-center justify-center text-xs font-bold text-white shrink-0">
                        {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif
                <div class="text-sm min-w-0">
                    <p class="text-ink font-medium truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-ink-muted">Admin</p>
                </div>
            </div>
            <a href="{{ route('designs.form') }}"
               class="mt-3 flex items-center gap-2 text-xs text-ink-muted hover:text-ink transition">
                <i class="fas fa-arrow-left"></i> Back to app
            </a>
        </div>

    </aside>

    {{-- ── Main content ──────────────────────────────────────────────── --}}
    <main class="lg:ml-64 p-4 lg:p-8 pt-20 lg:pt-8">
        @yield('content')
    </main>

</div>

@stack('scripts')
</body>
</html>
