<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_empty_cart_for_guest(): void
    {
        $response = $this->get('/api/cart');

        $response->assertOk();
        $response->assertJsonPath('data.status', 'open');
        $response->assertJsonPath('data.item_count', 0);
        $response->assertCookie('cart_token');
    }

    public function test_add_item_creates_cart_item(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/1001' => Http::response($this->fakeProduct(1001, 150), 200),
        ]);

        $response = $this->postJson('/api/cart/items', [
            'producto_id' => 1001,
            'qty' => 2,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.items.0.producto_id', 1001);
        $response->assertJsonPath('data.items.0.qty', 2);
        $response->assertJsonPath('data.subtotal', 300);
        $response->assertJsonPath('data.item_count', 2);
        $response->assertCookie('cart_token');
    }

    public function test_add_item_validates_payload(): void
    {
        $response = $this->postJson('/api/cart/items', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['producto_id']);
    }

    public function test_add_item_accepts_header_token(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/2002' => Http::response($this->fakeProduct(2002, 50), 200),
        ]);

        $token = str_repeat('a', 64);

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 2002, 'qty' => 1]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.items.0.qty', 1);
        $this->assertDatabaseHas('carts', ['session_token' => $token, 'status' => 'open']);
    }

    public function test_authenticated_user_has_persistent_cart(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/8008' => Http::response($this->fakeProduct(8008, 99), 200),
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/cart/items', ['producto_id' => 8008, 'qty' => 1])->assertStatus(201);

        $this->assertDatabaseHas('carts', ['user_id' => $user->id, 'status' => 'open']);
        $this->assertDatabaseHas('cart_items', ['producto_id' => 8008, 'qty' => 1]);
    }

    public function test_cart_page_renders(): void
    {
        $response = $this->get('/cart');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Cart/Index'));
    }

    public function test_cart_page_ignores_encrypted_cookie_and_creates_fresh_cart(): void
    {
        $encryptedCookie = 'eyJpdiI6Iks1SlUzOGVsUEpMdGZNVEFQZmVGYlE9PSIsInZhbHVlIjoiMUFweHhuZWlaNmc1SDU4b3NUaHF6QkRQTmlSbzREZXlwRGdSbVNabVFPUDhMSndEV1MrUEZLNUoxeC8yeEdWU1I0aXVuZzh0RitFVGNTdlB6aXI0S0gydHVYSGZJamV3ZXQ2ZExzWjdDUDJ1dDl6aVdMdmE4emRBd3pTK3dTU0pUUnpWbzNJRTJFeldQQmlSQ3VndUJnPT0iLCJtYWMiOiI0YzkxZDRlMTM1MGY5MTMyZDIxNjAzNGIxNTVlYzQzZDk3ZDU1YjIzNGNiNmI2YjZjM2Q0N2FjNjZiMzkxNDExIiwidGFnIjoiIn0=';

        $response = $this->withCookie('cart_token', $encryptedCookie)->get('/cart');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Cart/Index'));

        $this->assertDatabaseMissing('carts', ['session_token' => $encryptedCookie]);
    }

    public function test_cart_resolver_rejects_tokens_with_invalid_characters(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/9100' => Http::response($this->fakeProduct(9100, 12), 200),
        ]);

        $invalidToken = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.signature';

        $response = $this->withHeaders(['X-Cart-Token' => $invalidToken])
            ->postJson('/api/cart/items', ['producto_id' => 9100, 'qty' => 1]);

        $response->assertStatus(201);
        $this->assertDatabaseMissing('carts', ['session_token' => $invalidToken]);
        $created = \App\Models\Cart::where('status', 'open')->latest()->first();
        $this->assertNotNull($created);
        $this->assertNotSame($invalidToken, $created->session_token);
        $this->assertEquals(64, strlen((string) $created->session_token));
    }

    public function test_show_returns_same_cart_with_header_token(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/3003' => Http::response($this->fakeProduct(3003, 20), 200),
        ]);

        $token = str_repeat('b', 64);

        $add = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 3003, 'qty' => 2]);
        $add->assertStatus(201);

        $show = $this->withHeaders(['X-Cart-Token' => $token])->get('/api/cart');
        $show->assertOk();
        $show->assertJsonPath('data.item_count', 2);
    }

    public function test_update_item_qty_via_header(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/4004' => Http::response($this->fakeProduct(4004, 30), 200),
        ]);

        $token = str_repeat('c', 64);

        $added = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 4004, 'qty' => 1]);
        $itemId = $added->json('data.items.0.id');

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->patchJson("/api/cart/items/{$itemId}", ['qty' => 5]);

        $response->assertOk();
        $response->assertJsonPath('data.items.0.qty', 5);
        $response->assertJsonPath('data.subtotal', 150);
    }

    public function test_delete_item_via_header(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/5005' => Http::response($this->fakeProduct(5005, 80), 200),
        ]);

        $token = str_repeat('d', 64);

        $added = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 5005, 'qty' => 1]);
        $itemId = $added->json('data.items.0.id');

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->deleteJson("/api/cart/items/{$itemId}");

        $response->assertOk();
        $response->assertJsonPath('data.item_count', 0);
    }

    public function test_clear_cart_via_header(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/6006' => Http::response($this->fakeProduct(6006, 15), 200),
        ]);

        $token = str_repeat('e', 64);

        $first = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 6006, 'qty' => 2]);
        $first->assertSuccessful();
        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 6006, 'qty' => 1])
            ->assertSuccessful();

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->deleteJson('/api/cart');

        $response->assertOk();
        $response->assertJsonPath('data.item_count', 0);
    }

    public function test_add_same_item_increments_qty_via_header(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/7007' => Http::response($this->fakeProduct(7007, 20), 200),
        ]);

        $token = str_repeat('f', 64);

        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 7007, 'qty' => 2])
            ->assertStatus(201);

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 7007, 'qty' => 3]);

        $response->assertJsonPath('data.items.0.qty', 5);
        $response->assertJsonPath('data.subtotal', 100);
    }

    public function test_update_qty_to_zero_removes_item(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/9009' => Http::response($this->fakeProduct(9009, 10), 200),
        ]);

        $token = str_repeat('g', 64);

        $added = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 9009, 'qty' => 1]);
        $itemId = $added->json('data.items.0.id');

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->patchJson("/api/cart/items/{$itemId}", ['qty' => 0]);

        $response->assertOk();
        $response->assertJsonPath('data.item_count', 0);
    }

    protected function fakeProduct(int $id, float $price): array
    {
        return [
            'producto_id' => $id,
            'titulo' => "Test Product $id",
            'modelo' => "MDL-$id",
            'marca' => 'TestBrand',
            'img_portada' => "https://picsum.photos/seed/$id/200",
            'total_existencia' => 5,
            'nombre' => "Test Product $id",
            'etiqueta' => null,
            'categorias' => [],
            'garantia' => null,
            'precios' => [
                'precio_1' => $price,
                'precio_descuento' => 0,
                'precio_especial' => 0,
                'precio_lista' => $price,
            ],
        ];
    }
}
