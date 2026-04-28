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
        $now = now();

        // Only reset users whose last reset was in a previous month/year (or never set)
        $count = 0;

        User::query()
            ->where(function ($q) use ($now) {
                $q->whereNull('tokens_reset_at')
                  ->orWhereRaw('YEAR(tokens_reset_at) < ?', [$now->year])
                  ->orWhereRaw('MONTH(tokens_reset_at) < ? AND YEAR(tokens_reset_at) = ?', [$now->month, $now->year]);
            })
            ->each(function (User $user) use ($now, &$count) {
                $user->update([
                    'tokens'          => User::creditsForPlan($user->plan ?? 'free'),
                    'tokens_reset_at' => $now->copy()->startOfMonth(),
                ]);
                $count++;
            });

        $this->info("Reset credits for {$count} user(s).");

        return self::SUCCESS;
    }
}
