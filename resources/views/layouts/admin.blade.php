<!DOCTYPE html>
<html lang="en" style="background:#0d0d0d">
<head>
    <link rel="icon" href="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FabricAI — @yield('title', 'Admin')</title>
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-[#0d0d0d] text-white min-h-screen antialiased">

<div x-data="{ open: false }" class="min-h-screen">

    <header class="lg:hidden fixed top-0 left-0 right-0 z-40 h-14 px-4 flex items-center gap-3 backdrop-blur-sm"
            style="background:rgba(17,17,17,0.92);border-bottom:1px solid rgba(255,255,255,0.08)">
        <button @click="open = true" class="w-9 h-9 flex items-center justify-center rounded-lg text-white/60 hover:text-white transition"
                style="border:1px solid rgba(255,255,255,0.1)">
            <i class="fas fa-bars"></i>
        </button>
        <a href="/"><img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="FabricAI" class="h-7 w-7"></a>
        <span class="font-serif text-sm text-white/85">FabricAI Admin</span>
    </header>

    <div x-show="open"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="lg:hidden fixed inset-0 z-40 bg-black/55 backdrop-blur-sm"
         style="display:none;"></div>

    <aside class="w-64 bg-[#111] border-r flex flex-col fixed h-full z-50 -translate-x-full lg:translate-x-0 transition-transform duration-200"
           :class="{ 'translate-x-0': open }"
           style="border-color:rgba(255,255,255,0.07)">

        <div class="p-5 flex items-center gap-3" style="border-bottom:1px solid rgba(255,255,255,0.07)">
            <a href="/"><img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="FabricAI" class="h-10 w-10"></a>
            <div class="flex-1 min-w-0">
                <p class="font-serif text-sm text-white">fabricAI</p>
                <p class="text-[9px] uppercase tracking-[0.22em]" style="color:#9d5bc7">admin panel</p>
            </div>
            <button @click="open = false" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-lg text-white/40 hover:text-white transition">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <nav class="flex-1 p-3 space-y-1.5">
            <a href="{{ route('admin.dashboard') }}" @click="open = false"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-white/45 hover:text-white' }}"
               style="{{ request()->routeIs('admin.dashboard') ? 'background:rgba(124,60,160,0.22);border:1px solid rgba(124,60,160,0.35)' : 'border:1px solid transparent' }}">
                <i class="fas fa-chart-pie w-5 text-center"></i>
                Dashboard
            </a>
            <a href="{{ route('admin.users') }}" @click="open = false"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('admin.users') ? 'text-white' : 'text-white/45 hover:text-white' }}"
               style="{{ request()->routeIs('admin.users') ? 'background:rgba(124,60,160,0.22);border:1px solid rgba(124,60,160,0.35)' : 'border:1px solid transparent' }}">
                <i class="fas fa-users w-5 text-center"></i>
                Users
            </a>
            <a href="{{ route('admin.api-costs') }}" @click="open = false"
               class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition {{ request()->routeIs('admin.api-costs') ? 'text-white' : 'text-white/45 hover:text-white' }}"
               style="{{ request()->routeIs('admin.api-costs') ? 'background:rgba(124,60,160,0.22);border:1px solid rgba(124,60,160,0.35)' : 'border:1px solid transparent' }}">
                <i class="fas fa-coins w-5 text-center"></i>
                API Costs
            </a>
        </nav>

        <div class="p-4" style="border-top:1px solid rgba(255,255,255,0.07)">
            <div class="flex items-center gap-3">
                @if(Auth::user()->avatar)
                    <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}" class="w-8 h-8 rounded-full object-cover shrink-0">
                @else
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0" style="background:#7c3ca0">
                        {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif
                <div class="text-sm min-w-0">
                    <p class="text-white/80 font-medium truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] text-white/35 uppercase tracking-wider">Admin</p>
                </div>
            </div>
            <a href="{{ route('designs.form') }}" class="mt-3.5 inline-flex items-center gap-2 text-xs text-white/45 hover:text-white transition">
                <i class="fas fa-arrow-left"></i> Back to studio
            </a>
        </div>

    </aside>

    <main class="lg:ml-64 p-4 lg:p-8 pt-20 lg:pt-8">
        @yield('content')
    </main>

</div>

@stack('scripts')
</body>
</html>
