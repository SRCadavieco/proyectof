<x-guest-layout>
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-white mb-2">Welcome back</h1>
        <p class="text-gray-400 text-sm">Sign in to continue designing with AI</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="username"
                   class="w-full px-4 py-3 rounded-xl bg-gray-900 border border-gray-700
                          text-white placeholder-gray-500 text-sm
                          focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent
                          transition" />
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-red-400 text-xs" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Password</label>
            <input id="password"
                   type="password"
                   name="password"
                   required autocomplete="current-password"
                   class="w-full px-4 py-3 rounded-xl bg-gray-900 border border-gray-700
                          text-white placeholder-gray-500 text-sm
                          focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent
                          transition" />
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-red-400 text-xs" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-gray-700 bg-gray-900 text-purple-500
                              focus:ring-purple-500 focus:ring-offset-gray-950">
                <span class="text-sm text-gray-400">Remember me</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-sm text-purple-400 hover:text-purple-300 transition">
                    Forgot password?
                </a>
            @endif
        </div>

        <!-- Submit -->
        <button type="submit"
                class="w-full py-3 rounded-xl font-semibold text-sm text-white
                       bg-gradient-to-r from-purple-500 to-indigo-500
                       shadow-lg shadow-purple-500/30
                       hover:opacity-90 hover:scale-[1.01] transition-all duration-200">
            Sign in
        </button>

        <!-- Divider -->
        <div class="relative flex items-center gap-3 py-1">
            <div class="flex-1 h-px bg-gray-800"></div>
            <span class="text-xs text-gray-600">OR</span>
            <div class="flex-1 h-px bg-gray-800"></div>
        </div>

        <!-- Register CTA -->
        <p class="text-center text-sm text-gray-400">
            Don't have an account?
            <a href="{{ route('register') }}"
               class="text-purple-400 font-semibold hover:text-purple-300 transition">
                Create one for free
            </a>
        </p>
    </form>
</x-guest-layout>
