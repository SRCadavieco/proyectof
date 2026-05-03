<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source',
        'event_type',
        'description',
        'plan',
        'tokens',
        'amount_usd',
        'currency',
        'reference',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'amount_usd' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
