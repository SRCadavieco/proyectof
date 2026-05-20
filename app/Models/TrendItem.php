<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrendItem extends Model
{
    protected $fillable = [
        'cluster_id',
        'listing_id',
        'similarity_score',
    ];

    protected $casts = [
        'similarity_score' => 'float',
    ];

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(TrendCluster::class, 'cluster_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(EtsyListing::class, 'listing_id');
    }
}
