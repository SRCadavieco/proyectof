<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLog extends Model
{
    protected $fillable = [
        'service',
        'model',
        'operation',
        'user_id',
        'cost_usd',
        'success',
    ];

    protected $casts = [
        'cost_usd' => 'float',
        'success'  => 'boolean',
    ];

    // Estimated cost per call (USD) — adjust as pricing changes
    public const COST_MAP = [
        'together' => [
            'flux_dev'             => 0.05,
            'black-forest-labs/FLUX.2-dev' => 0.05,
            'default'              => 0.05,
        ],
        'chutes' => [
            'z_image_turbo' => 0.02,
            'flux_schnell'  => 0.02,
            'default'       => 0.02,
        ],
        'gemini' => [
            'fabric_light' => 0.0,
            'fabric_pro'   => 0.0,
            'default'      => 0.0,
        ],
        'nanogpt' => [
            'juggernaut_z' => 0.066,
            'default'      => 0.066,
        ],
        'rnbulktools' => [
            'remove_bg'  => 0.01,
            'default'    => 0.01,
        ],
    ];

    public static function estimateCost(string $service, ?string $model): float
    {
        $map = self::COST_MAP[$service] ?? [];
        return $map[$model] ?? $map['default'] ?? 0.0;
    }

    public static function record(string $service, ?string $model, string $operation, ?int $userId, bool $success = true): void
    {
        static::create([
            'service'   => $service,
            'model'     => $model,
            'operation' => $operation,
            'user_id'   => $userId,
            'cost_usd'  => $success ? static::estimateCost($service, $model) : 0.0,
            'success'   => $success,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
