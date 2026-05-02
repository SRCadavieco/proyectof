<?php

use App\Models\User;
use App\Models\PrintifyConnection;
use App\Services\PrintifyService;
use Illuminate\Support\Facades\Http;

// ─────────────────────────────────────────────
// Auth guard
// ─────────────────────────────────────────────

it('connect requiere autenticación', function () {
    $this->post(route('printify.connect'), ['api_token' => 'tok_test'])
        ->assertRedirect(route('login'));
});

it('status requiere autenticación', function () {
    $this->get(route('printify.status'))
        ->assertRedirect(route('login'));
});

it('create product requiere autenticación', function () {
    $this->postJson(route('printify.products'), [])
        ->assertStatus(401);
});

// ─────────────────────────────────────────────
// Status endpoint
// ─────────────────────────────────────────────

it('status devuelve connected false si no hay conexión', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('printify.status'))
        ->assertOk()
        ->assertJsonFragment(['connected' => false]);
});

it('status devuelve connected true si hay conexión', function () {
    $user = User::factory()->create();
    PrintifyConnection::create([
        'user_id'   => $user->id,
        'api_token' => 'tok_test',
        'shop_id'   => 99,
        'shop_name' => 'My Shop',
    ]);

    $this->actingAs($user)
        ->getJson(route('printify.status'))
        ->assertOk()
        ->assertJsonFragment(['connected' => true]);
});

// ─────────────────────────────────────────────
// Connect — validation
// ─────────────────────────────────────────────

it('connect rechaza token vacío', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('printify.connect'), [])
        ->assertSessionHasErrors('api_token');
});

it('connect con token inválido muestra error de API', function () {
    Http::fake([
        'api.printify.com/*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('printify.connect'), ['api_token' => 'bad_token'])
        ->assertSessionHasErrors(['api_token']);
});

it('connect con token válido guarda la conexión', function () {
    Http::fake([
        'api.printify.com/v1/shops.json' => Http::response([
            ['id' => 123, 'title' => 'My Shop'],
        ], 200),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('printify.connect'), ['api_token' => 'valid_token'])
        ->assertSessionHas('printify_success');

    $this->assertDatabaseHas('printify_connections', [
        'user_id'  => $user->id,
        'shop_id'  => 123,
    ]);
});

// ─────────────────────────────────────────────
// Disconnect
// ─────────────────────────────────────────────

it('disconnect elimina la conexión del usuario', function () {
    $user = User::factory()->create();
    PrintifyConnection::create([
        'user_id'   => $user->id,
        'api_token' => 'tok_test',
    ]);

    $this->actingAs($user)
        ->delete(route('printify.disconnect'))
        ->assertSessionHas('printify_success');

    $this->assertDatabaseMissing('printify_connections', ['user_id' => $user->id]);
});

// ─────────────────────────────────────────────
// Create product — validation
// ─────────────────────────────────────────────

it('create product devuelve 401 si no hay conexión Printify', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('printify.products'), [
            'shop_id'      => 1,
            'garment_type' => 'tshirt',
            'image_source' => 'data:image/png;base64,abc',
            'title'        => 'Test Product',
        ])
        ->assertStatus(401)
        ->assertJsonFragment(['error' => 'Printify not connected']);
});

it('create product valida garment_type', function () {
    $user = User::factory()->create();
    PrintifyConnection::create([
        'user_id'   => $user->id,
        'api_token' => 'tok_test',
    ]);

    $this->actingAs($user)
        ->postJson(route('printify.products'), [
            'shop_id'      => 1,
            'garment_type' => 'invalid_garment',
            'image_source' => 'data:image/png;base64,abc',
            'title'        => 'Test Product',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['garment_type']);
});

it('create product con servicio mockeado devuelve URL de Printify', function () {
    $user = User::factory()->create();
    $conn = PrintifyConnection::create([
        'user_id'   => $user->id,
        'api_token' => 'tok_test',
        'shop_id'   => 99,
    ]);

    $fakeProduct = ['id' => '123abc', 'title' => 'Test Product'];

    $mock = Mockery::mock(PrintifyService::class);
    $mock->shouldReceive('sendDesign')
        ->once()
        ->andReturn($fakeProduct);
    app()->instance(PrintifyService::class, $mock);

    $this->actingAs($user)
        ->postJson(route('printify.products'), [
            'shop_id'      => 99,
            'garment_type' => 'tshirt',
            'image_source' => 'data:image/png;base64,' . base64_encode(str_repeat('x', 100)),
            'title'        => 'Test Product',
        ])
        ->assertOk()
        ->assertJsonFragment(['success' => true])
        ->assertJsonFragment(['product' => $fakeProduct]);
});

it('create product acepta todos los tipos de prenda válidos', function (string $garment) {
    $user = User::factory()->create();
    PrintifyConnection::create([
        'user_id'   => $user->id,
        'api_token' => 'tok_test',
    ]);

    $mock = Mockery::mock(PrintifyService::class);
    $mock->shouldReceive('sendDesign')->andReturn(['id' => 'x', 'title' => 'T']);
    app()->instance(PrintifyService::class, $mock);

    $this->actingAs($user)
        ->postJson(route('printify.products'), [
            'shop_id'      => 1,
            'garment_type' => $garment,
            'image_source' => 'data:image/png;base64,abc',
            'title'        => 'Test',
        ])
        ->assertOk();
})->with(['tshirt', 'hoodie', 'zip_hoodie', 'tanktop', 'longsleeve', 'sweatshirt', 'vneck', 'womens_tee']);
