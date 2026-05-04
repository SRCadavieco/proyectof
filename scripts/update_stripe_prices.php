<?php
/**
 * Script para actualizar precios en Stripe
 * Starter: $7
 * Pro: $12
 * Business: $25
 * 
 * Ejecutar con: php scripts/update_stripe_prices.php
 */
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

echo "=== Actualizar Precios de Stripe ===\n\n";

// Crear productos si no existen
echo "Creando productos...\n";

// Starter product
$starterProd = $stripe->products->create(['name' => 'FabricAI Starter']);
echo "Starter Product: {$starterProd->id}\n";

// Starter monthly price ($7)
$starterMonthly = $stripe->prices->create([
    'product' => $starterProd->id,
    'unit_amount' => 700, // $7.00 USD
    'currency' => 'usd',
    'recurring' => ['interval' => 'month'],
]);
echo "Starter Monthly: {$starterMonthly->id}\n";

// Pro product
$proProd = $stripe->products->create(['name' => 'FabricAI Pro']);
echo "\nPro Product: {$proProd->id}\n";

// Pro monthly price ($12)
$proMonthly = $stripe->prices->create([
    'product' => $proProd->id,
    'unit_amount' => 1200, // $12.00 USD
    'currency' => 'usd',
    'recurring' => ['interval' => 'month'],
]);
echo "Pro Monthly: {$proMonthly->id}\n";

// Business product
$businessProd = $stripe->products->create(['name' => 'FabricAI Business']);
echo "\nBusiness Product: {$businessProd->id}\n";

// Business monthly price ($25)
$businessMonthly = $stripe->prices->create([
    'product' => $businessProd->id,
    'unit_amount' => 2500, // $25.00 USD
    'currency' => 'usd',
    'recurring' => ['interval' => 'month'],
]);
echo "Business Monthly: {$businessMonthly->id}\n";

echo "\n--- Add these to your .env ---\n";
echo "STRIPE_STARTER_MONTHLY_PRICE={$starterMonthly->id}\n";
echo "STRIPE_PRO_MONTHLY_PRICE={$proMonthly->id}\n";
echo "STRIPE_BUSINESS_MONTHLY_PRICE={$businessMonthly->id}\n";
echo "\n✓ Precios actualizados correctamente\n";
