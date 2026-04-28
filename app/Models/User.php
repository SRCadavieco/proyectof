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
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'last_login_at'     => 'datetime',
            'tokens_reset_at'   => 'datetime',
        ];
    }

    /**
     * Credits (tokens) granted per plan per month.
     */
    public static function creditsForPlan(string $plan): int
    {
        return match ($plan) {
            'starter'  => 80,
            'pro'      => 200,
            'business' => 500,
            default    => 5,   // free
        };
    }

    /**
     * Lazily reset monthly credits if a new calendar month has started
     * since the last reset. Call this before any credit check/deduction.
     */
    public function refreshCreditsIfNeeded(): void
    {
        $now = now();

        // If never reset, or the stored reset was in a previous month/year → reset
        if (
            is_null($this->tokens_reset_at) ||
            $this->tokens_reset_at->month !== $now->month ||
            $this->tokens_reset_at->year  !== $now->year
        ) {
            $this->update([
                'tokens'          => self::creditsForPlan($this->plan ?? 'free'),
                'tokens_reset_at' => $now->startOfMonth(),
            ]);
        }
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
}
