<x-guest-layout>
    <div class="mb-8">
        <h1 class="font-serif text-3xl text-ink mb-2">Welcome back</h1>
        <p class="text-ink-muted text-sm">Sign in to continue designing</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-medium text-ink-muted uppercase tracking-wider mb-2">Email</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="username"
                   class="w-full px-4 py-3 bg-white border border-cream-300
                          text-ink placeholder-ink-muted/40 text-sm
                          focus:outline-none focus:ring-1 focus:ring-ink focus:border-ink
                          transition" />
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-red-600 text-xs" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-xs font-medium text-ink-muted uppercase tracking-wider mb-2">Password</label>
            <input id="password"
                   type="password"
                   name="password"
                   required autocomplete="current-password"
                   class="w-full px-4 py-3 bg-white border border-cream-300
                          text-ink placeholder-ink-muted/40 text-sm
                          focus:outline-none focus:ring-1 focus:ring-ink focus:border-ink
                          transition" />
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-red-600 text-xs" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-cream-300 bg-white text-ink
                              focus:ring-ink focus:ring-offset-cream-50">
                <span class="text-sm text-ink-muted">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-sm text-ink-muted hover:text-ink transition-colors underline">
                    Forgot password?
                </a>
            @endif
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-primary w-full text-center">
            Sign in
        </button>

        <!-- Google OAuth -->
        <a href="{{ route('auth.google') }}"
           class="flex items-center justify-center gap-3 w-full px-4 py-3 bg-white border border-cream-300
                  text-ink text-sm font-medium hover:bg-cream-50 transition">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Continue with Google
        </a>

        <!-- Divider -->
        <div class="relative flex items-center gap-3 py-1">
            <div class="flex-1 h-px bg-cream-300"></div>
            <span class="text-xs text-ink-muted">or</span>
            <div class="flex-1 h-px bg-cream-300"></div>
        </div>

        <!-- Register CTA -->
        <p class="text-center text-sm text-ink-muted">
            Don't have an account?
            <a href="{{ route('register') }}"
               class="text-ink font-medium hover:underline transition">
                Create one for free
            </a>
        </p>
    </form>
</x-guest-layout>
