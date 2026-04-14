<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                array_filter([
                    'name'              => $googleUser->getName(),
                    'google_id'         => $googleUser->getId(),
                    'email_verified_at' => now(),
                ], fn($v) => $v !== null)
            );

            // Set password nullable only if user was just created without one
            if (! $user->password) {
                $user->password = null;
                $user->saveQuietly();
            }

            Auth::login($user, true);

            return redirect()->route('designs.form');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google OAuth error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'Google authentication failed. Please try again.']);
        }
    }
}
