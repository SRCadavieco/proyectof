<!-- ================= NAVBAR ================= -->
<nav
    x-data="{ scrolled: false }"
    @scroll.window="scrolled = window.scrollY > 50"
    :class="scrolled
        ? 'bg-gray-950/90 backdrop-blur-md border-b border-gray-800'
        : 'bg-transparent'"
    class="fixed w-full z-50 transition-all duration-500"
>
    <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">

        <!-- Logo -->
        <div class="flex items-center">
            <a href="/">
                <img src="/images/logo.png" alt="FabricAI" class="h-16 w-16">
            </a>
        </div>

        <!-- Desktop links -->
        <div class="hidden md:flex gap-8 text-sm text-gray-300 font-medium">
            <a href="/#how-it-works"
               class="hover:text-purple-400 transition {{ request()->is('/#how-it-works')}}">
                How it works
            </a>
            <a href="/pricing"
               class="hover:text-purple-400 transition {{ request()->is('pricing')}}">
                Pricing
            </a>
            <a href="/faq"
               class="hover:text-purple-400 transition {{ request()->is('faq')}}">
                FAQ
            </a>
        </div>

        <!-- CTA -->
        @auth
            <div class="flex items-center gap-3">
                
                <!-- Avatar dropdown -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                    class="w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-indigo-500
                    flex items-center justify-center text-white text-sm font-bold
                    hover:opacity-90 transition select-none focus:outline-none
                    ring-2 ring-transparent hover:ring-purple-500/50">
                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                </button>
                
                <!-- Dropdown -->
                <div x-show="open"
                x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute right-0 mt-2 w-44 rounded-xl bg-gray-900 border border-gray-800
                         shadow-xl shadow-black/40 py-1 z-50"
                         style="display:none;">
                         <div class="px-4 py-2 border-b border-gray-800">
                             <p class="text-xs text-gray-400 truncate">{{ Auth::user()->name }}</p>
                             <p class="text-xs text-gray-600 truncate">{{ Auth::user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 transition">
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
                <a href="{{ route('designs.form') }}"
                   class="px-5 py-2 rounded-lg bg-gradient-to-r from-purple-500 to-indigo-500 text-sm font-semibold text-white hover:opacity-90 transition">
                    My Studio
                </a>
            </div>
            @else
            <a href="{{ route('login') }}"
               class="px-5 py-2 rounded-lg bg-gradient-to-r from-purple-500 to-indigo-500 text-sm font-semibold text-white hover:opacity-90 transition">
                Sign in / Register
            </a>
        @endauth

    </div>
</nav>
