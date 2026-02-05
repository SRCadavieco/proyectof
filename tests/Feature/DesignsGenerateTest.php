<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
// Disable all middleware in these tests for simplicity

it('valida que el prompt es requerido', function () {
    config(['services.gemini.url' => 'https://example.com', 'services.gemini.token' => 't']);
    Http::fake();

    $user = User::factory()->create();
    $this->withoutMiddleware()
        ->actingAs($user)
        ->postJson(route('designs.generate'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['prompt']);
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

    $this->withoutMiddleware()
        ->actingAs($user)
        ->postJson(route('designs.generate'), ['prompt' => 'Una landing moderna'])
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

    $this->withoutMiddleware()
        ->actingAs($user)
        ->postJson(route('designs.generate'), ['prompt' => 'Prueba'])
        ->assertStatus(500)
        ->assertJsonFragment(['success' => false]);
});

it('falla claramente si falta configuración', function () {
    config(['services.gemini.url' => null, 'services.gemini.token' => null]);
    Http::fake();

    $user = User::factory()->create();

    $this->withoutMiddleware()
        ->actingAs($user)
        ->postJson(route('designs.generate'), ['prompt' => 'Prueba'])
        ->assertStatus(500)
        ->assertJsonFragment(['code' => 'config_error']);
});
