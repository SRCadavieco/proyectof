<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GiveDailyCredits extends Command
{
    protected $signature   = 'credits:give-daily';
    protected $description = 'Grant daily tokens to all paid users who have not yet reached their monthly cap.';

    public function handle(): int
    {
        $count = 0;

        // Only process paid plans that have a daily rate.
        // Admin plan is restricted to admin users.
        User::where(function ($query) {
            $query->whereIn('plan', ['starter', 'pro', 'business'])
                ->orWhere(function ($adminQuery) {
                    $adminQuery->where('plan', 'admin')->where('is_admin', true);
                });
        })
            ->each(function (User $user) use (&$count) {
                $daily = User::dailyCreditsForPlan($user->plan ?? 'free');
                if ($daily <= 0) return;

                // Already granted today?
                if ($user->daily_tokens_given_at && $user->daily_tokens_given_at->isToday()) return;

                // Lazily grant upfront tokens if a new month started (in case the scheduler ran late)
                $user->refreshCreditsIfNeeded();
                $user->refresh();

                $monthlyMax = User::creditsForPlan($user->plan ?? 'free');
                $givenSoFar = $user->tokens_given_this_month ?? 0;
                $canGive    = min($daily, $monthlyMax - $givenSoFar);
                if ($canGive <= 0) return;

                $user->update([
                    'tokens'                  => $user->tokens + $canGive,
                    'tokens_given_this_month' => $givenSoFar + $canGive,
                    'daily_tokens_given_at'   => now(),
                ]);

                $count++;
            });

        $this->info("Granted daily tokens to {$count} user(s).");

        return self::SUCCESS;
    }
}
