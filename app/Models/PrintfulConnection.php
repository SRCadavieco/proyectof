<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintfulConnection extends Model
{
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'store_id',
        'store_name',
    ];

    protected $casts = [
        'access_token_expires_at' => 'datetime',
        'store_id' => 'integer',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAccessTokenExpired(): bool
    {
        if (! $this->access_token_expires_at) {
            return false;
        }
        return $this->access_token_expires_at->isPast();
    }
}
