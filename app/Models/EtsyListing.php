<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtsyListing extends Model
{
    protected $fillable = [
        'keyword',
        'title',
        'price',
        'url',
        'image',
        'tags',
        'raw_json',
    ];

    protected $casts = [
        'tags'     => 'array',
        'raw_json' => 'array',
    ];

    public function trendItems(): HasMany
    {
        return $this->hasMany(TrendItem::class, 'listing_id');
    }
}
