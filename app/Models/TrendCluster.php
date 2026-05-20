<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TrendCluster extends Model
{
    protected $fillable = [
        'name',
        'summary',
        'design_prompt',
        'top_keywords',
        'embedding_vector',
        'score',
        'growth_rate',
        'competition_score',
        'listing_count',
        'keyword',
    ];

    protected $casts = [
        'top_keywords'     => 'array',
        'embedding_vector' => 'array',
        'score'            => 'float',
        'growth_rate'      => 'float',
        'competition_score'=> 'float',
        'listing_count'    => 'integer',
    ];

    public function trendItems(): HasMany
    {
        return $this->hasMany(TrendItem::class, 'cluster_id');
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(
            EtsyListing::class,
            'trend_items',
            'cluster_id',
            'listing_id'
        )->withPivot('similarity_score')->withTimestamps();
    }
}
