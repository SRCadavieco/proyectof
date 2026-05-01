<!-- ================= NAVBAR ================= -->
<style>
    nav.nav-dark-hero .nav-link       { color: rgba(255,255,255,0.72); }
    nav.nav-dark-hero .nav-link:hover { color: #fff; }
</style>
<nav
    x-data="{ scrolled: false, mobileOpen: false, darkHero: {{ isset($navDarkHero) && $navDarkHero ? 'true' : 'false' }} }"
    @scroll.window="scrolled = window.scrollY > 50"
    :class="scrolled
        ? 'bg-cream-50/95 backdrop-blur-md border-b border-cream-300'
        : (darkHero ? 'bg-transparent nav-dark-hero' : 'bg-transparent')"
    class="fixed w-full z-50 transition-all duration-500"
>
    <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">

        <!-- Logo -->
        <div class="flex items-center">
            <a href="/" class="flex items-center gap-3">
                <img src="/images/logo.png" alt="FabricAI" class="h-12 w-12">
                <span class="font-serif text-xl text-ink hidden sm:block">FabricAI</span>
            </a>
        </div>

        <!-- Desktop links -->
        <div class="hidden md:flex gap-10 text-sm font-medium tracking-wide uppercase"
             :class="darkHero && !scrolled ? '' : 'text-ink-muted'">
            <a href="/#how-it-works"
               class="nav-link link-underline transition-colors duration-300 pb-0.5"
               :class="!(darkHero && !scrolled) ? 'hover:text-ink text-ink-muted' : ''">
                How it works
            </a>
            <a href="/pricing"
               class="nav-link link-underline transition-colors duration-300 pb-0.5"
               :class="!(darkHero && !scrolled) ? 'hover:text-ink text-ink-muted' : ''">
                Pricing
            </a>
            <a href="/faq"
               class="nav-link link-underline transition-colors duration-300 pb-0.5"
               :class="!(darkHero && !scrolled) ? 'hover:text-ink text-ink-muted' : ''">
                FAQ
            </a>
        </div>

        <!-- CTA -->
        @auth
            <div class="flex items-center gap-4">
                
                <!-- Avatar dropdown -->
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                            class="w-9 h-9 rounded-full overflow-hidden bg-ink
                                   flex items-center justify-center text-white text-sm font-bold
                                   hover:ring-2 hover:ring-purple-400 transition-all select-none focus:outline-none">
                        @if(Auth::user()->avatar)
                            <img src="{{ Auth::user()->avatar }}" alt="{{ Auth::user()->name }}"
                                 class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                        @endif
                    </button>
                
                <!-- Dropdown -->
                <div x-show="open"
                x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute right-0 mt-2 w-48 bg-white border border-cream-300
                         rounded-lg shadow-lg shadow-black/5 py-1 z-50"
                         style="display:none;">
                         <div class="px-4 py-3 border-b border-cream-200">
                             <p class="text-sm font-medium text-ink truncate">{{ Auth::user()->name }}</p>
                             <p class="text-xs text-ink-muted truncate">{{ Auth::user()->email }}</p>
                            </div>
                            <a href="{{ route('profile.show') }}"
                               class="block px-4 py-2.5 text-sm text-ink-light hover:text-ink hover:bg-cream-100 transition-colors">
                                <i class="fas fa-user mr-2"></i>My Profile
                            </a>
                            @if(Auth::user()->is_admin)
                                <a href="{{ route('admin.dashboard') }}"
                                   class="block px-4 py-2.5 text-sm text-ink-light hover:text-ink hover:bg-cream-100 transition-colors">
                                    <i class="fas fa-shield-halved mr-2"></i>Admin Panel
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                class="w-full text-left px-4 py-2.5 text-sm text-ink-muted hover:text-ink hover:bg-cream-100 transition-colors">
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
                <a href="{{ route('designs.form') }}"
                   class="btn-primary text-xs px-6 py-2.5">
                    My Studio
                </a>
            </div>
            @else
            <a href="{{ route('login') }}"
               class="btn-primary text-xs px-6 py-2.5">
                Sign in
            </a>
        @endauth

    </div>
</nav>
