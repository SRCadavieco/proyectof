<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$clusters = DB::table('trend_clusters')->orderByDesc('score')->limit(5)->get();
foreach ($clusters as $c) {
    echo "[{$c->id}] {$c->name}\n";
    echo "  design_prompt: " . ($c->design_prompt ?: '(empty)') . "\n\n";
}
