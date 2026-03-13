<x-guest-layout>
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-white mb-2">Create your account</h1>
        <p class="text-gray-400 text-sm">Start generating AI-powered fashion designs today</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Name</label>
            <input id="name"
                   type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required autofocus autocomplete="name"
                   class="w-full px-4 py-3 rounded-xl bg-gray-900 border border-gray-700
                          text-white placeholder-gray-500 text-sm
                          focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent
                          transition" />
            <x-input-error :messages="$errors->get('name')" class="mt-1 text-red-400 text-xs" />
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required autocomplete="username"
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
                   required autocomplete="new-password"
                   class="w-full px-4 py-3 rounded-xl bg-gray-900 border border-gray-700
                          text-white placeholder-gray-500 text-sm
                          focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent
                          transition" />
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-red-400 text-xs" />
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1">Confirm Password</label>
            <input id="password_confirmation"
                   type="password"
                   name="password_confirmation"
                   required autocomplete="new-password"
                   class="w-full px-4 py-3 rounded-xl bg-gray-900 border border-gray-700
                          text-white placeholder-gray-500 text-sm
                          focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent
                          transition" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-red-400 text-xs" />
        </div>

        <!-- Submit -->
        <button type="submit"
                class="w-full py-3 rounded-xl font-semibold text-sm text-white
                       bg-gradient-to-r from-purple-500 to-indigo-500
                       shadow-lg shadow-purple-500/30
                       hover:opacity-90 hover:scale-[1.01] transition-all duration-200">
            Create account
        </button>

        <!-- Divider -->
        <div class="relative flex items-center gap-3 py-1">
            <div class="flex-1 h-px bg-gray-800"></div>
            <span class="text-xs text-gray-600">OR</span>
            <div class="flex-1 h-px bg-gray-800"></div>
        </div>

        <!-- Login CTA -->
        <p class="text-center text-sm text-gray-400">
            Already have an account?
            <a href="{{ route('login') }}"
               class="text-purple-400 font-semibold hover:text-purple-300 transition">
                Sign in
            </a>
        </p>
    </form>
</x-guest-layout>
