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
