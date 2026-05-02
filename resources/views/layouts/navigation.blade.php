<!-- ================= NAVBAR ================= -->
<style>
    nav.nav-dark-hero .nav-link       { color: rgba(255,255,255,0.72); }
    nav.nav-dark-hero .nav-link:hover { color: #fff; }
</style>
<nav
    x-data="{ scrolled: false, mobileOpen: false, darkHero: {{ isset($navDarkHero) && $navDarkHero ? 'true' : 'false' }} }"
    @scroll.window="scrolled = window.scrollY > 50"
    :class="scrolled
        ? (darkHero ? 'bg-[#0d0d0d]/80 backdrop-blur-md border-b border-white/10 nav-dark-hero' : 'bg-cream-50/95 backdrop-blur-md border-b border-cream-300')
        : (darkHero ? 'bg-transparent nav-dark-hero' : 'bg-transparent')"
    class="fixed top-0 left-0 right-0 w-full z-50 transition-all duration-500"
>
    <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">

        <!-- Logo -->
        <div class="flex items-center">
            <a href="/" class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}?v={{ filemtime(public_path('images/logo.png')) }}" alt="FabricAI" class="h-12 w-12">
                <span class="font-serif text-xl hidden sm:block transition-colors duration-500"
                      :class="darkHero ? 'text-white' : 'text-ink'">FabricAI</span>
            </a>
        </div>

        <!-- Desktop links -->
        <div class="hidden md:flex gap-10 text-sm font-medium tracking-wide uppercase"
             :class="darkHero ? '' : 'text-ink-muted'">
            <a href="/#how-it-works"
               class="nav-link link-underline transition-colors duration-300 pb-0.5"
               :class="!darkHero ? 'hover:text-ink text-ink-muted' : ''">
                How it works
            </a>
            <a href="/pricing"
               class="nav-link link-underline transition-colors duration-300 pb-0.5"
               :class="!darkHero ? 'hover:text-ink text-ink-muted' : ''">
                Pricing
            </a>
            <a href="/faq"
               class="nav-link link-underline transition-colors duration-300 pb-0.5"
               :class="!darkHero ? 'hover:text-ink text-ink-muted' : ''">
                FAQ
            </a>
        </div>

        <!-- Mobile hamburger -->
        <button @click="mobileOpen = !mobileOpen"
                class="md:hidden flex flex-col justify-center items-center w-9 h-9 gap-1.5 rounded-lg transition-colors"
                :class="darkHero ? 'text-white hover:bg-white/10' : 'text-ink hover:bg-cream-200'"
                aria-label="Menu">
            <span class="block w-5 h-px transition-all duration-300 bg-current"
                  :class="mobileOpen ? 'rotate-45 translate-y-[7px]' : ''"></span>
            <span class="block w-5 h-px bg-current transition-all duration-300"
                  :class="mobileOpen ? 'opacity-0' : ''"></span>
            <span class="block w-5 h-px transition-all duration-300 bg-current"
                  :class="mobileOpen ? '-rotate-45 -translate-y-[7px]' : ''"></span>
        </button>

        <!-- CTA -->
        @auth
            <div class="hidden md:flex items-center gap-4">
                
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
               class="hidden md:inline-flex btn-primary text-xs px-6 py-2.5">
                Sign in
            </a>
        @endauth

    </div>

    <!-- Mobile drawer -->
    <div x-show="mobileOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden border-t"
         :class="scrolled || !darkHero
             ? 'bg-cream-50/97 border-cream-300'
             : 'bg-[#0d0d0d]/95 border-white/10'"
         style="display:none;">
        <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col gap-1">
            <a href="/#how-it-works" @click="mobileOpen = false"
               class="px-3 py-3 text-sm font-medium rounded-xl transition-colors"
               :class="scrolled || !darkHero ? 'text-ink-muted hover:text-ink hover:bg-cream-100' : 'text-white/60 hover:text-white hover:bg-white/8'">
                How it works
            </a>
            <a href="/pricing" @click="mobileOpen = false"
               class="px-3 py-3 text-sm font-medium rounded-xl transition-colors"
               :class="scrolled || !darkHero ? 'text-ink-muted hover:text-ink hover:bg-cream-100' : 'text-white/60 hover:text-white hover:bg-white/8'">
                Pricing
            </a>
            <a href="/faq" @click="mobileOpen = false"
               class="px-3 py-3 text-sm font-medium rounded-xl transition-colors"
               :class="scrolled || !darkHero ? 'text-ink-muted hover:text-ink hover:bg-cream-100' : 'text-white/60 hover:text-white hover:bg-white/8'">
                FAQ
            </a>
            <div class="border-t mt-2 pt-3" :class="scrolled || !darkHero ? 'border-cream-200' : 'border-white/8'">
                @auth
                <a href="{{ route('designs.form') }}" @click="mobileOpen = false"
                   class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                   style="background:#7c3ca0">
                    My Studio
                </a>
                @else
                <a href="{{ route('login') }}" @click="mobileOpen = false"
                   class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-sm font-semibold text-white transition-opacity hover:opacity-90"
                   style="background:#7c3ca0">
                    Sign in
                </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

@include('layouts.printify-popup')

