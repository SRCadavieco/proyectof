<x-guest-layout>
    <div class="mb-8">
        <h1 class="font-serif text-3xl text-ink mb-2">Create your account</h1>
        <p class="text-ink-muted text-sm">Start generating AI-powered fashion designs today</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-xs font-medium text-ink-muted uppercase tracking-wider mb-2">Name</label>
            <input id="name"
                   type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required autofocus autocomplete="name"
                   class="w-full px-4 py-3 bg-white border border-cream-300
                          text-ink placeholder-ink-muted/40 text-sm
                          focus:outline-none focus:ring-1 focus:ring-ink focus:border-ink
                          transition" />
            <x-input-error :messages="$errors->get('name')" class="mt-1 text-red-600 text-xs" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-medium text-ink-muted uppercase tracking-wider mb-2">Email</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required autocomplete="username"
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
                   required autocomplete="new-password"
                   class="w-full px-4 py-3 bg-white border border-cream-300
                          text-ink placeholder-ink-muted/40 text-sm
                          focus:outline-none focus:ring-1 focus:ring-ink focus:border-ink
                          transition" />
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-red-600 text-xs" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-xs font-medium text-ink-muted uppercase tracking-wider mb-2">Confirm Password</label>
            <input id="password_confirmation"
                   type="password"
                   name="password_confirmation"
                   required autocomplete="new-password"
                   class="w-full px-4 py-3 bg-white border border-cream-300
                          text-ink placeholder-ink-muted/40 text-sm
                          focus:outline-none focus:ring-1 focus:ring-ink focus:border-ink
                          transition" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-red-600 text-xs" />
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-primary w-full text-center">
            Create account
        </button>

        <!-- Divider -->
        <div class="relative flex items-center gap-3 py-1">
            <div class="flex-1 h-px bg-cream-300"></div>
            <span class="text-xs text-ink-muted">or</span>
            <div class="flex-1 h-px bg-cream-300"></div>
        </div>

        <!-- Login CTA -->
        <p class="text-center text-sm text-ink-muted">
            Already have an account?
            <a href="{{ route('login') }}"
               class="text-ink font-medium hover:underline transition">
                Sign in
            </a>
        </p>
    </form>
</x-guest-layout>
