<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
// Disable all middleware in these tests for simplicity

// ─────────────────────────────────────────────
// Auth guard
// ─────────────────────────────────────────────

it('generate requiere autenticación', function () {
    $this->postJson(route('designs.generate'), ['prompt' => 'test'])
        ->assertStatus(401);
});

it('la página de diseño requiere autenticación', function () {
    $this->get(route('designs.form'))
        ->assertRedirect(route('login'));
});

it('la página de diseño carga para usuario autenticado', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('designs.form'))
        ->assertOk();
});

// ─────────────────────────────────────────────
// Prompt validation
// ─────────────────────────────────────────────

it('valida que el prompt es requerido', function () {
    config(['services.gemini.url' => 'https://example.com', 'services.gemini.token' => 't']);
    Http::fake();

    $user = User::factory()->create();
    $chat = \App\Models\Chat::create(['user_id' => $user->id, 'title' => 'Test']);
    $this->withoutMiddleware()
        ->actingAs($user)
        ->postJson(route('designs.generate'), ['chat_id' => $chat->id])
        ->assertStatus(422)
        ->assertJsonFragment(['error' => 'Error de validación'])
        ->assertJsonPath('details.prompt', fn ($v) => !empty($v));
});

it('devuelve 200 con respuesta del backend', function () {
    config(['services.gemini.url' => 'https://example.com', 'services.gemini.token' => 't']);

    Http::fake([
        'https://example.com/*' => Http::response([
            'imageUrl' => 'https://cdn.example.com/img.png',
            'meta' => ['model' => 'gemini'],
        ], 200),
    ]);

    $user = User::factory()->create();
    $chat = \App\Models\Chat::create(['user_id' => $user->id, 'title' => 'Test']);

    $this->withoutMiddleware()
        ->actingAs($user)
        ->postJson(route('designs.generate'), ['prompt' => 'Una landing moderna', 'chat_id' => $chat->id])
        ->assertStatus(200)
        ->assertJsonFragment(['imageUrl' => 'https://cdn.example.com/img.png']);
});

it('propaga el error del backend con status', function () {
    config(['services.gemini.url' => 'https://example.com', 'services.gemini.token' => 't']);

    Http::fake([
        'https://example.com/*' => Http::response([
            'error' => 'Backend error',
        ], 500),
    ]);

    $user = User::factory()->create();
    $chat = \App\Models\Chat::create(['user_id' => $user->id, 'title' => 'Test']);

    $this->withoutMiddleware()
        ->actingAs($user)
        ->postJson(route('designs.generate'), ['prompt' => 'Prueba', 'chat_id' => $chat->id])
        ->assertStatus(500)
        ->assertJsonFragment(['success' => false]);
});

it('falla claramente si falta configuración', function () {
    config(['services.gemini.url' => null, 'services.gemini.token' => null]);
    Http::fake();

    $user = User::factory()->create();
    $chat = \App\Models\Chat::create(['user_id' => $user->id, 'title' => 'Test']);

    $this->withoutMiddleware()
        ->actingAs($user)
        ->postJson(route('designs.generate'), ['prompt' => 'Prueba', 'chat_id' => $chat->id])
        ->assertStatus(500)
        ->assertJsonFragment(['success' => false]);
});
