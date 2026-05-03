<?php

use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionController;
use App\Models\BillingEvent;
use App\Models\User;

it('resuelve correctamente los planes desde Stripe price id', function () {
    config()->set('services.stripe.prices', [
        'starter_monthly' => 'price_starter_test',
        'pro_monthly' => 'price_pro_test',
        'business_monthly' => 'price_business_test',
    ]);

    expect(SubscriptionController::planFromPriceId('price_starter_test'))->toBe('starter');
    expect(SubscriptionController::planFromPriceId('price_pro_test'))->toBe('pro');
    expect(SubscriptionController::planFromPriceId('price_business_test'))->toBe('business');
    expect(SubscriptionController::planFromPriceId('price_unknown'))->toBeNull();
});

it('webhook de suscripcion actualiza plan y registra actividad de compra de plan', function () {
    config()->set('services.stripe.prices', [
        'starter_monthly' => 'price_starter_test',
        'pro_monthly' => 'price_pro_test',
        'business_monthly' => 'price_business_test',
    ]);

    $user = User::factory()->create([
        'stripe_id' => 'cus_test_123',
        'plan' => 'free',
        'tokens' => 5,
    ]);

    $payload = [
        'id' => 'evt_test_sub_updated_1',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_test_123',
                'customer' => 'cus_test_123',
                'status' => 'active',
                'items' => [
                    'data' => [[
                        'id' => 'si_test_123',
                        'price' => [
                            'id' => 'price_pro_test',
                            'product' => 'prod_test_123',
                        ],
                    ]],
                ],
            ],
        ],
    ];

    app(StripeWebhookController::class)->handleCustomerSubscriptionUpdated($payload);

    $user->refresh();

    expect($user->plan)->toBe('pro');
    expect($user->tokens)->toBe(User::creditsForPlan('pro'));

    $event = BillingEvent::where('user_id', $user->id)
        ->where('event_type', 'plan_purchase')
        ->first();

    expect($event)->not()->toBeNull();
    expect($event->plan)->toBe('pro');
});

it('webhook de credit pack acredita tokens una sola vez y registra transaccion', function () {
    $user = User::factory()->create(['tokens' => 0]);

    $payload = [
        'data' => [
            'object' => [
                'id' => 'cs_test_credits_1',
                'mode' => 'payment',
                'payment_status' => 'paid',
                'amount_total' => 1000,
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'credits' => '140',
                    'pack' => 'large',
                ],
            ],
        ],
    ];

    $controller = app(StripeWebhookController::class);
    $controller->handleCheckoutSessionCompleted($payload);
    $controller->handleCheckoutSessionCompleted($payload);

    $user->refresh();

    expect($user->tokens)->toBe(140);

    $events = BillingEvent::where('user_id', $user->id)
        ->where('event_type', 'token_purchase')
        ->where('reference', 'cs_test_credits_1')
        ->get();

    expect($events)->toHaveCount(1);
    expect((int) $events->first()->tokens)->toBe(140);
});

it('admin users muestra transacciones recientes y actividad reciente', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    BillingEvent::create([
        'user_id' => $user->id,
        'source' => 'stripe',
        'event_type' => 'plan_purchase',
        'description' => 'Usuario compra plan PRO',
        'plan' => 'pro',
        'tokens' => 200,
        'reference' => 'sub_ui_test_1',
    ]);

    BillingEvent::create([
        'user_id' => $user->id,
        'source' => 'stripe',
        'event_type' => 'token_purchase',
        'description' => 'Usuario ha comprado 60 tokens',
        'tokens' => 60,
        'reference' => 'cs_ui_test_1',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSee('Recent Transactions')
        ->assertSee('Recent Activity')
        ->assertSee('Usuario compra plan PRO')
        ->assertSee('Usuario ha comprado 60 tokens');
});
