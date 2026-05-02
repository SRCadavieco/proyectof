<x-app-layout>
    <x-slot name="title">Edit Profile</x-slot>

    {{-- ── Page header ── --}}
    <div class="bg-ink">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 flex items-center justify-between">
            <div>
                <a href="{{ route('profile.show') }}"
                   class="inline-flex items-center gap-1.5 text-xs text-white/50 hover:text-white transition-colors mb-3">
                    <i class="fas fa-arrow-left text-[10px]"></i> Back to profile
                </a>
                <h1 class="font-serif text-2xl text-white">Edit Profile</h1>
                <p class="text-white/50 text-sm mt-1">Update your account information</p>
            </div>
            @if($user->avatar)
                <img src="{{ $user->avatar }}" alt="{{ $user->name }}"
                     class="w-12 h-12 rounded-full object-cover border-2 border-white/20 shrink-0 hidden sm:block">
            @else
                <div class="w-12 h-12 rounded-full border-2 border-white/20 bg-white/10 flex items-center justify-center text-white text-xl font-bold select-none shrink-0 hidden sm:block">
                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
            @endif
        </div>
    </div>

    <div class="min-h-screen bg-cream-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-6">

            {{-- ── Block 1: Account Info ── --}}
            <div class="bg-white border border-cream-200 p-6 sm:p-8">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    <h2 class="text-base font-serif text-ink">Account Info</h2>
                </div>
                <p class="text-xs text-ink-muted mb-6">Update your username and profile photo.</p>

                @if(session('status') === 'profile-updated')
                    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm">
                        Profile updated successfully.
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <div class="flex gap-6 items-end">
                        {{-- Username --}}
                        <div class="flex-1">
                            <x-input-label for="name" value="Username" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full max-w-sm"
                                :value="old('name', $user->name)" required autocomplete="name" />
                            <x-input-error :messages="$errors->updateProfile->get('name')" class="mt-2" />
                        </div>

                        {{-- Avatar with hover overlay --}}
                        <div class="shrink-0 flex flex-col items-center gap-1">
                            <span class="text-xs text-ink-muted mb-1">Photo</span>
                            <label for="avatar-input" class="group relative cursor-pointer">
                                @if($user->avatar)
                                    <img src="{{ $user->avatar }}" alt="{{ $user->name }}"
                                         id="avatar-preview"
                                         class="w-20 h-20 rounded-full object-cover border border-cream-200">
                                @else
                                    <div id="avatar-initials"
                                         class="w-20 h-20 rounded-full bg-ink flex items-center justify-center text-white text-2xl font-bold select-none">
                                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </div>
                                    <img id="avatar-preview" class="w-20 h-20 rounded-full object-cover border border-cream-200 hidden" alt="Preview">
                                @endif
                                {{-- Hover overlay --}}
                                <div class="absolute inset-0 rounded-full bg-black/50 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z" />
                                    </svg>
                                    <span class="text-white text-[10px] mt-0.5 font-medium">Change</span>
                                </div>
                                <input type="file" name="avatar" id="avatar-input"
                                       accept="image/jpeg,image/png,image/webp,image/gif"
                                       class="sr-only">
                            </label>
                            <x-input-error :messages="$errors->updateProfile->get('avatar')" class="mt-1" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                                class="px-6 py-2.5 bg-ink text-white text-xs font-medium tracking-widest uppercase hover:bg-purple-900 transition-colors">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

            {{-- ── Block 2: Email ── --}}
            <div class="bg-white border border-cream-200 p-6 sm:p-8">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                    <h2 class="text-base font-serif text-ink">Email</h2>
                </div>
                <p class="text-xs text-ink-muted mb-6">Change your login email address. You'll need to confirm your current password.</p>

                @if(session('status') === 'email-updated')
                    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm">
                        Email updated successfully.
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.update-email') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label value="Current Email" />
                        <div class="mt-1 w-full border border-cream-200 bg-cream-50 px-3 py-2 text-sm text-ink-muted">
                            {{ $user->email }}
                        </div>
                    </div>

                    <div>
                        <x-input-label for="new_email" value="New Email" />
                        <x-text-input id="new_email" name="email" type="email" class="mt-1 block w-full max-w-sm"
                            :value="old('email')" autocomplete="off" />
                        <x-input-error :messages="$errors->updateEmail->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email_confirmation" value="Confirm New Email" />
                        <x-text-input id="email_confirmation" name="email_confirmation" type="email" class="mt-1 block w-full max-w-sm"
                            autocomplete="off" />
                        <x-input-error :messages="$errors->updateEmail->get('email_confirmation')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email_password" value="Password" />
                        <x-text-input id="email_password" name="password" type="password" class="mt-1 block w-full max-w-sm"
                            autocomplete="current-password" />
                        <x-input-error :messages="$errors->updateEmail->get('password')" class="mt-2" />
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                                class="px-6 py-2.5 bg-ink text-white text-xs font-medium tracking-widest uppercase hover:bg-purple-900 transition-colors">
                            Update Email
                        </button>
                    </div>
                </form>
            </div>

            {{-- ── Block 3: Password ── --}}
            <div class="bg-white border border-cream-200 p-6 sm:p-8">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <h2 class="text-base font-serif text-ink">Password</h2>
                </div>
                <p class="text-xs text-ink-muted mb-6">Use a long, random password to keep your account secure.</p>

                @if(session('status') === 'password-updated')
                    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm">
                        Password updated successfully.
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="current_password" value="Current Password" />
                        <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full max-w-sm"
                            autocomplete="current-password" />
                        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="New Password" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full max-w-sm"
                            autocomplete="new-password" />
                        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirm Password" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full max-w-sm"
                            autocomplete="new-password" />
                        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                                class="px-6 py-2.5 bg-ink text-white text-xs font-medium tracking-widest uppercase hover:bg-purple-900 transition-colors">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            {{-- ── Block 4: Connections ── --}}
            <div class="bg-white border border-cream-200 p-6 sm:p-8 space-y-6">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-ink-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                    </svg>
                    <h2 class="text-base font-serif text-ink">Connections</h2>
                </div>

                {{-- Printify --}}
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-sm font-medium text-ink">Printify</span>
                        @php $conn = Auth::user()->printifyConnection; @endphp
                        @if($conn)
                            <span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-50 border border-green-200 px-2 py-0.5">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0Z" clip-rule="evenodd"/></svg>
                                Connected{{ $conn->shop_name ? ' · ' . $conn->shop_name : '' }}
                            </span>
                        @else
                            <span class="inline-flex items-center text-xs text-ink-muted bg-cream-50 border border-cream-200 px-2 py-0.5">Not connected</span>
                        @endif
                    </div>
                    <p class="text-xs text-ink-muted mb-3">Connect your Printify account to send designs directly to your print-on-demand store.</p>

                    @if(session('printify_error'))
                        <div class="mb-3 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('printify_error') }}</div>
                    @endif
                    @if(session('printify_success'))
                        <div class="mb-3 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm">{{ session('printify_success') }}</div>
                    @endif
                    @if($errors->has('api_token'))
                        <div class="mb-3 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm">{{ $errors->first('api_token') }}</div>
                    @endif

                    @if($conn)
                        <form method="POST" action="{{ route('printify.disconnect') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2 border border-red-300 text-red-600 text-xs font-medium tracking-wide uppercase hover:bg-red-50 transition-colors"
                                    onclick="return confirm('Disconnect your Printify account?')">
                                Disconnect
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('printify.connect') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-xs text-ink-muted mb-1 uppercase tracking-wider">Printify API Token</label>
                                <input type="text" name="api_token" placeholder="Paste your Printify API token here"
                                       class="w-full border border-cream-300 px-3 py-2 text-sm text-ink focus:outline-none focus:border-ink transition-colors"
                                       autocomplete="off">
                            </div>
                            <button type="submit"
                                    class="px-6 py-2.5 bg-ink text-white text-xs font-medium tracking-widest uppercase hover:bg-purple-900 transition-colors">
                                Connect Printify
                            </button>
                        </form>
                        <p class="mt-3 text-xs text-ink-muted">
                            Get your token at <a href="https://printify.com/app/account#api" target="_blank" rel="noopener noreferrer" class="underline">printify.com → My account → Connections → API</a>.
                        </p>
                    @endif
                </div>

                {{-- Divider --}}
                <div class="border-t border-cream-200"></div>

                {{-- Google --}}
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-sm font-medium text-ink">Google</span>
                        @if($user->google_id)
                            <span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-50 border border-green-200 px-2 py-0.5">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 0 1 0 1.414l-8 8a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L8 12.586l7.293-7.293a1 1 0 0 1 1.414 0Z" clip-rule="evenodd"/></svg>
                                Connected
                            </span>
                        @else
                            <span class="inline-flex items-center text-xs text-ink-muted bg-cream-50 border border-cream-200 px-2 py-0.5">
                                Not connected
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-ink-muted">
                        @if($user->google_id)
                            Your account is linked to Google. You can sign in with Google.
                        @else
                            Link your Google account to sign in faster.
                            <a href="{{ route('auth.google') }}" class="underline hover:text-ink transition-colors">Connect Google →</a>
                        @endif
                    </p>
                </div>

                {{-- Divider --}}
                <div class="border-t border-cream-200"></div>

                {{-- Danger Zone --}}
                <div>
                    <h3 class="text-sm font-medium text-red-600 mb-1">Danger Zone</h3>
                    <p class="text-xs text-ink-muted mb-4">Once deleted, all your data will be permanently removed. This action cannot be undone.</p>
                    <button type="button"
                            onclick="document.getElementById('delete-account-modal').classList.remove('hidden')"
                            class="px-6 py-2.5 border border-red-300 text-red-600 text-xs font-medium tracking-widest uppercase hover:bg-red-50 transition-colors">
                        Delete Account
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- Delete Account Modal --}}
    <div id="delete-account-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
        <div class="bg-white border border-cream-200 p-8 w-full max-w-md">
            <h2 class="text-lg font-serif text-ink mb-2">Delete your account?</h2>
            <p class="text-sm text-ink-muted mb-6">This action is irreversible. All your data will be permanently deleted. Enter your password to confirm.</p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <x-input-label for="delete_password" value="Password" class="sr-only" />
                    <x-text-input id="delete_password" name="password" type="password" class="block w-full"
                        placeholder="Your password" autocomplete="current-password" />
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button"
                            onclick="document.getElementById('delete-account-modal').classList.add('hidden')"
                            class="px-5 py-2.5 border border-cream-300 text-ink text-xs font-medium tracking-widest uppercase hover:bg-cream-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 bg-red-600 text-white text-xs font-medium tracking-widest uppercase hover:bg-red-700 transition-colors">
                        Delete Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('avatar-input');
            if (input) {
                input.addEventListener('change', function () {
                    const file = this.files[0];
                    if (!file) return;

                    // Show preview immediately, then auto-submit the form
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const preview = document.getElementById('avatar-preview');
                        const initials = document.getElementById('avatar-initials');
                        if (preview) {
                            preview.src = e.target.result;
                            preview.classList.remove('hidden');
                        }
                        if (initials) initials.classList.add('hidden');

                        // Auto-submit so the file is definitely in the request
                        input.form.submit();
                    };
                    reader.readAsDataURL(file);
                });
            }

            @if($errors->userDeletion->isNotEmpty())
                document.getElementById('delete-account-modal').classList.remove('hidden');
            @endif
        })();
    </script>
</x-app-layout>
