<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name'              => $googleUser->getName(),
                    'google_id'         => $googleUser->getId(),
                    'email_verified_at' => now(),
                ]
            );

            // Seed credit metadata for brand-new Google users.
            if ($user->wasRecentlyCreated) {
                $user->plan                  = 'free';
                $user->tokens                = User::creditsForPlan('free');
                $user->tokens_reset_at       = now()->startOfMonth();
                $user->tokens_given_this_month = User::upfrontCreditsForPlan('free');
                $user->daily_tokens_given_at = null;
                $user->saveQuietly();
            }

            Auth::login($user, remember: true);

            return redirect()->route('designs.form');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google OAuth error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'Google authentication failed. Please try again.']);
        }
    }
}
