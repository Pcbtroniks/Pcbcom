<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use App\Services\Payment\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    private function fakeProduct(int $id, float $price): array
    {
        return [
            'producto_id' => $id,
            'titulo' => "Test $id",
            'modelo' => "MDL-$id",
            'marca' => 'Brand',
            'img_portada' => null,
            'total_existencia' => 5,
            'nombre' => "Test $id",
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

    private function shippingAddress(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cliente Prueba',
            'phone' => '+52 33 1234 5678',
            'line1' => 'Av. Reforma 100',
            'city' => 'Guadalajara',
            'state' => 'Jalisco',
            'zip' => '44100',
            'country' => 'MX',
        ], $overrides);
    }

    public function test_preview_computes_shipping_and_tax(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/101' => Http::response($this->fakeProduct(101, 100), 200),
        ]);

        $token = str_repeat('a', 64);

        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 101, 'qty' => 2])
            ->assertSuccessful();

        $response = $this->withHeaders(['X-Cart-Token' => $token])->get('/api/checkout/preview');
        $response->assertOk();
        $response->assertJsonPath('data.subtotal', 200);
        $response->assertJsonPath('data.shipping', 99);
        $response->assertJsonPath('data.tax', 32);
        $response->assertJsonPath('data.total', 331);
    }

    public function test_preview_fails_with_empty_cart(): void
    {
        $token = str_repeat('b', 64);
        $response = $this->withHeaders(['X-Cart-Token' => $token])->get('/api/checkout/preview');
        $response->assertStatus(422);
    }

    public function test_confirm_creates_order_with_null_gateway(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/201' => Http::response($this->fakeProduct(201, 50), 200),
        ]);

        $token = str_repeat('c', 64);

        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 201, 'qty' => 1])
            ->assertSuccessful();

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/checkout/confirm', [
                'shipping_address' => $this->shippingAddress(),
                'payment_method' => 'null',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'paid');
        $response->assertJsonPath('data.total', 157);
        $response->assertJsonPath('data.subtotal', 50);
        $response->assertJsonPath('data.shipping', 99);
        $response->assertJsonPath('data.tax', 8);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $order = Order::first();
        $this->assertNotNull($order->payment_intent_id);
        $this->assertStringStartsWith('pi_null_', (string) $order->payment_intent_id);
        $this->assertNotNull($order->paid_at);
    }

    public function test_confirm_validates_address(): void
    {
        $token = str_repeat('d', 64);

        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/301' => Http::response($this->fakeProduct(301, 25), 200),
        ]);

        $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/cart/items', ['producto_id' => 301, 'qty' => 1])
            ->assertSuccessful();

        $response = $this->withHeaders(['X-Cart-Token' => $token])
            ->postJson('/api/checkout/confirm', [
                'shipping_address' => ['name' => 'Solo nombre'],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'shipping_address.phone',
            'shipping_address.line1',
            'shipping_address.city',
            'shipping_address.state',
            'shipping_address.zip',
        ]);
    }

    public function test_confirm_is_idempotent_with_key(): void
    {
        $order = Order::factory()->create([
            'idempotency_key' => 'ck_unique_test_key',
            'status' => 'paid',
        ]);

        $cart = Cart::factory()->forGuest()->create();
        CartItem::factory()->for($cart)->create();

        $service = app(\App\Services\Cart\CheckoutService::class);
        $found = $service->findIdempotent('ck_unique_test_key');

        $this->assertNotNull($found);
        $this->assertSame($order->number, $found->number);
    }

    public function test_authenticated_user_persists_order_with_user_id(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/501' => Http::response($this->fakeProduct(501, 10), 200),
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->postJson('/api/cart/items', ['producto_id' => 501, 'qty' => 1])->assertSuccessful();

        $response = $this->postJson('/api/checkout/confirm', [
            'shipping_address' => $this->shippingAddress(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
    }

    public function test_show_order_by_number(): void
    {
        $order = Order::factory()->paid()->create();

        $response = $this->getJson("/api/checkout/orders/{$order->number}");
        $response->assertOk();
        $response->assertJsonPath('data.number', $order->number);
    }

    public function test_webhook_marks_order_paid(): void
    {
        $order = Order::factory()->create([
            'payment_intent_id' => 'pi_test_123',
            'status' => 'pending',
            'paid_at' => null,
        ]);

        $response = $this->postJson('/api/webhooks/payment', [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'status' => 'succeeded',
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('order', $order->number);
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_webhook_for_unknown_intent_is_acknowledged(): void
    {
        $response = $this->postJson('/api/webhooks/payment', [
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_unknown', 'status' => 'succeeded']],
        ]);

        $response->assertOk();
        $response->assertJsonPath('matched', false);
    }

    public function test_webhook_marks_order_cancelled_on_failure(): void
    {
        $order = Order::factory()->create([
            'payment_intent_id' => 'pi_test_fail',
            'status' => 'pending',
        ]);

        $this->postJson('/api/webhooks/payment', [
            'type' => 'payment_intent.payment_failed',
            'data' => ['object' => ['id' => 'pi_test_fail', 'status' => 'failed']],
        ])->assertOk();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
    }

    public function test_stripe_gateway_returns_pending_result(): void
    {
        $this->app->bind(PaymentGateway::class, fn () => new \App\Services\Payment\StripeGateway());

        Http::fake([
            'api.stripe.com/*' => Http::response([
                'id' => 'pi_stripe_xyz',
                'status' => 'requires_payment_method',
                'client_secret' => 'pi_stripe_xyz_secret_abc',
            ], 200),
        ]);

        config()->set('payment.methods.stripe.secret_key', 'sk_test_fake');

        $order = Order::factory()->create([
            'subtotal' => 100,
            'shipping' => 0,
            'tax' => 16,
            'total' => 116,
        ]);

        $gateway = app(PaymentGateway::class);
        $result = $gateway->charge($order);

        $this->assertTrue($result->success);
        $this->assertSame('pending', $result->status);
        $this->assertSame('pi_stripe_xyz', $result->paymentIntentId);
    }
}
