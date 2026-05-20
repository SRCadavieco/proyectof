<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TrendCluster;
use App\Models\EtsyListing;

echo "=== EtsyListings: " . EtsyListing::count() . " ===" . PHP_EOL;
echo "=== TrendClusters: " . TrendCluster::count() . " ===" . PHP_EOL . PHP_EOL;

$clusters = TrendCluster::orderByDesc('score')->limit(8)->get();
foreach ($clusters as $c) {
    echo "  [{$c->id}] {$c->name}" . PHP_EOL;
    echo "      score=" . round($c->score, 3)
        . " | growth=" . round($c->growth_rate, 3)
        . " | competition=" . round($c->competition_score, 3)
        . " | listings={$c->listing_count} | kw={$c->keyword}" . PHP_EOL;
    $kws = is_array($c->top_keywords) ? implode(', ', array_slice($c->top_keywords, 0, 5)) : '';
    echo "      top_keywords: $kws" . PHP_EOL;
}
