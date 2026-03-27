<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

// Create Pro product
$proProd = $stripe->products->create(['name' => 'FabricAI Pro']);
echo "Pro Product: {$proProd->id}\n";

// Pro monthly price (19 EUR)
$proMonthly = $stripe->prices->create([
    'product' => $proProd->id,
    'unit_amount' => 1900,
    'currency' => 'eur',
    'recurring' => ['interval' => 'month'],
]);
echo "Pro Monthly: {$proMonthly->id}\n";

// Pro yearly price (15 EUR/mo = 180 EUR/year)
$proYearly = $stripe->prices->create([
    'product' => $proProd->id,
    'unit_amount' => 18000,
    'currency' => 'eur',
    'recurring' => ['interval' => 'year'],
]);
echo "Pro Yearly: {$proYearly->id}\n";

// Create Studio product
$studioProd = $stripe->products->create(['name' => 'FabricAI Studio']);
echo "Studio Product: {$studioProd->id}\n";

// Studio monthly price (49 EUR)
$studioMonthly = $stripe->prices->create([
    'product' => $studioProd->id,
    'unit_amount' => 4900,
    'currency' => 'eur',
    'recurring' => ['interval' => 'month'],
]);
echo "Studio Monthly: {$studioMonthly->id}\n";

// Studio yearly price (39 EUR/mo = 468 EUR/year)
$studioYearly = $stripe->prices->create([
    'product' => $studioProd->id,
    'unit_amount' => 46800,
    'currency' => 'eur',
    'recurring' => ['interval' => 'year'],
]);
echo "Studio Yearly: {$studioYearly->id}\n";

echo "\n--- Add these to your .env ---\n";
echo "STRIPE_PRO_MONTHLY_PRICE={$proMonthly->id}\n";
echo "STRIPE_PRO_YEARLY_PRICE={$proYearly->id}\n";
echo "STRIPE_STUDIO_MONTHLY_PRICE={$studioMonthly->id}\n";
echo "STRIPE_STUDIO_YEARLY_PRICE={$studioYearly->id}\n";
