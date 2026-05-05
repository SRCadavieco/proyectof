<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'is_admin',
        'tokens',
        'plan',
        'tokens_reset_at',
        'tokens_given_this_month',
        'daily_tokens_given_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'password'                 => 'hashed',
            'is_admin'                 => 'boolean',
            'last_login_at'            => 'datetime',
            'tokens_reset_at'          => 'datetime',
            'daily_tokens_given_at'    => 'datetime',
        ];
    }

    /**
     * Tokens granted upfront at the start of each month.
     */
    public static function upfrontCreditsForPlan(string $plan): int
    {
        return match ($plan) {
            'starter'  => 50,
            'pro'      => 80,
            'business' => 200,
            'admin'    => 200,
            default    => 5,   // free: all 5 upfront, no daily
        };
    }

    /**
     * Tokens granted per day after the initial upfront grant.
     */
    public static function dailyCreditsForPlan(string $plan): int
    {
        return match ($plan) {
            'starter'  => 1,
            'pro'      => 4,
            'business' => 10,
            'admin'    => 10,
            default    => 0,
        };
    }

    /**
     * Maximum total tokens that can be granted in a single calendar month.
     */
    public static function creditsForPlan(string $plan): int
    {
        return match ($plan) {
            'starter'  => 80,
            'pro'      => 200,
            'business' => 500,
            'admin'    => 500,
            default    => 5,
        };
    }

    /**
     * Grant monthly upfront tokens if a new calendar month has started.
     * Tokens are ADDED to the existing balance (they never reset/expire).
     */
    public function refreshCreditsIfNeeded(): void
    {
        $now = now();

        if (
            is_null($this->tokens_reset_at) ||
            $this->tokens_reset_at->month !== $now->month ||
            $this->tokens_reset_at->year  !== $now->year
        ) {
            $upfront = self::upfrontCreditsForPlan($this->plan ?? 'free');
            $this->update([
                'tokens'                  => $this->tokens + $upfront,
                'tokens_given_this_month' => $upfront,
                'tokens_reset_at'         => $now->copy()->startOfMonth(),
                'daily_tokens_given_at'   => null,
            ]);
        }
    }

    /**
     * Grant today's daily tokens if not yet given today and the monthly cap has not been reached.
     */
    public function grantDailyCreditsIfNeeded(): void
    {
        $daily = self::dailyCreditsForPlan($this->plan ?? 'free');
        if ($daily <= 0) return;

        // Already granted today?
        if ($this->daily_tokens_given_at && $this->daily_tokens_given_at->isToday()) return;

        $monthlyMax = self::creditsForPlan($this->plan ?? 'free');
        $givenSoFar = $this->tokens_given_this_month ?? 0;
        $canGive    = min($daily, $monthlyMax - $givenSoFar);
        if ($canGive <= 0) return;

        $this->update([
            'tokens'                  => $this->tokens + $canGive,
            'tokens_given_this_month' => $givenSoFar + $canGive,
            'daily_tokens_given_at'   => now(),
        ]);
    }

    /**
     * Get the chats for the user.
     */
    public function chats()
    {
        return $this->hasMany(\App\Models\Chat::class);
    }

    public function printifyConnection()
    {
        return $this->hasOne(\App\Models\PrintifyConnection::class);
    }

    public function billingEvents()
    {
        return $this->hasMany(\App\Models\BillingEvent::class);
    }
}
