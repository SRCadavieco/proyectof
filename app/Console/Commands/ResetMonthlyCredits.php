<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetMonthlyCredits extends Command
{
    protected $signature   = 'credits:reset-monthly';
    protected $description = 'Reset every user\'s monthly credit allowance based on their current plan.';

    public function handle(): int
    {
        $now   = now();
        $count = 0;

        // Grant upfront tokens for users whose last reset was in a previous month/year (or never).
        // Tokens are ADDED to the existing balance — they never expire or reset.
        User::query()
            ->where(function ($q) use ($now) {
                $q->whereNull('tokens_reset_at')
                  ->orWhereRaw('YEAR(tokens_reset_at) < ?', [$now->year])
                  ->orWhereRaw('MONTH(tokens_reset_at) < ? AND YEAR(tokens_reset_at) = ?', [$now->month, $now->year]);
            })
            ->each(function (User $user) use ($now, &$count) {
                $upfront = User::upfrontCreditsForPlan($user->plan ?? 'free');
                $user->update([
                    'tokens'                  => $user->tokens + $upfront,
                    'tokens_given_this_month' => $upfront,
                    'tokens_reset_at'         => $now->copy()->startOfMonth(),
                    'daily_tokens_given_at'   => null,
                ]);
                $count++;
            });

        $this->info("Granted upfront monthly tokens to {$count} user(s).");

        return self::SUCCESS;
    }
}
