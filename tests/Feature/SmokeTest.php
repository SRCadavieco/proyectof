<?php

use App\Models\User;

// ─────────────────────────────────────────────
// Public pages load
// ─────────────────────────────────────────────

it('página de inicio carga sin autenticación', function () {
    $this->get(route('home'))->assertOk();
});

it('página de precios carga sin autenticación', function () {
    $this->get('/pricing')->assertOk();
});

it('página de FAQ carga sin autenticación', function () {
    $this->get('/faq')->assertOk();
});

it('página de términos carga sin autenticación', function () {
    $this->get('/terms')->assertOk();
});

it('página de privacidad carga sin autenticación', function () {
    $this->get('/privacy')->assertOk();
});

// ─────────────────────────────────────────────
// Auth pages load
// ─────────────────────────────────────────────

it('página de login carga', function () {
    $this->get(route('login'))->assertOk();
});

it('página de registro carga', function () {
    $this->get(route('register'))->assertOk();
});

// ─────────────────────────────────────────────
// Login / Logout
// ─────────────────────────────────────────────

it('usuario puede hacer login con credenciales correctas', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret1234'),
    ]);

    $this->post(route('login'), [
        'email'    => $user->email,
        'password' => 'secret1234',
    ])
        ->assertRedirect(route('designs.form'));

    $this->assertAuthenticatedAs($user);
});

it('login rechaza contraseña incorrecta', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct'),
    ]);

    $this->post(route('login'), [
        'email'    => $user->email,
        'password' => 'wrong',
    ])
        ->assertSessionHasErrors();

    $this->assertGuest();
});

it('usuario puede hacer logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});

// ─────────────────────────────────────────────
// Registration
// ─────────────────────────────────────────────

it('usuario puede registrarse', function () {
    $this->post(route('register'), [
        'name'                  => 'Tester',
        'email'                 => 'tester@example.com',
        'password'              => 'Password1!',
        'password_confirmation' => 'Password1!',
    ])
        ->assertRedirect(route('designs.form'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'tester@example.com']);
});

// ─────────────────────────────────────────────
// Profile (requires auth)
// ─────────────────────────────────────────────

it('perfil requiere autenticación', function () {
    $this->get(route('profile.show'))
        ->assertRedirect(route('login'));
});

it('perfil carga para usuario autenticado', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertOk();
});

// ─────────────────────────────────────────────
// Token API
// ─────────────────────────────────────────────

it('api tokens devuelve saldo del usuario', function () {
    $user = User::factory()->create(['tokens' => 42]);

    $this->actingAs($user)
        ->getJson(route('api.tokens'))
        ->assertOk()
        ->assertJsonFragment(['remaining' => 42]);
});
