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
        <a href="{{ route('login') }}"
           class="px-5 py-2 rounded-lg bg-gradient-to-r from-purple-500 to-indigo-500 text-sm font-semibold text-white hover:opacity-90 transition">
            Sign in / Register
        </a>

    </div>
</nav>
