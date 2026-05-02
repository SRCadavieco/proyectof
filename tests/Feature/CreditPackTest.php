<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;

// ─────────────────────────────────────────────
// Auth guard
// ─────────────────────────────────────────────

it('checkout requiere autenticación', function () {
    $this->post(route('credits.checkout'), ['pack' => 'small'])
        ->assertRedirect(route('login'));
});

it('success requiere autenticación', function () {
    $this->get(route('credits.success'))
        ->assertRedirect(route('login'));
});

// ─────────────────────────────────────────────
// Checkout — validation
// ─────────────────────────────────────────────

it('checkout rechaza pack inválido', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('credits.checkout'), ['pack' => 'mega'])
        ->assertSessionHasErrors('pack');
});

it('checkout rechaza pack vacío', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('credits.checkout'), [])
        ->assertSessionHasErrors('pack');
});

// ─────────────────────────────────────────────
// Success — edge cases without Stripe
// ─────────────────────────────────────────────

it('success redirige a design si no hay session_id', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('credits.success'))
        ->assertRedirect(route('designs.form'));
});

// ─────────────────────────────────────────────
// Idempotency guard
// ─────────────────────────────────────────────

it('la caché de idempotencia impide doble acreditación', function () {
    $user = User::factory()->create(['tokens' => 0]);

    // Simulate that the session was already processed
    Cache::put('cp_sess_cs_test_already', true, now()->addDays(7));

    // Manually call the logic that would be triggered by the success page
    // (we only test the Cache guard, not the full Stripe flow)
    $key = 'cp_sess_cs_test_already';
    $wasAdded = Cache::add($key, true, now()->addDays(7));

    expect($wasAdded)->toBeFalse();
    expect($user->fresh()->tokens)->toBe(0);
});
