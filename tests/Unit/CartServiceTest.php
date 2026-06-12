<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use App\Services\Cart\PricingService;
use App\Services\Syscom\ProductsService;
use App\Services\Syscom\WishlistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_resolver_creates_guest_cart_and_persists_token(): void
    {
        $resolver = app(CartResolver::class);
        $request = Request::create('/api/cart', 'GET');

        $cart = $resolver->resolve($request);

        $this->assertNotNull($cart->session_token);
        $this->assertNull($cart->user_id);
        $this->assertSame('open', $cart->status);
    }

    public function test_resolver_returns_existing_cart_for_repeated_token(): void
    {
        $resolver = app(CartResolver::class);
        $token = str_repeat('z', 64);
        $request = Request::create('/api/cart', 'GET');
        $request->headers->set('X-Cart-Token', $token);

        $first = $resolver->resolve($request);
        $second = $resolver->resolve($request);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($token, $first->session_token);
    }

    public function test_resolver_resolves_user_cart_after_login(): void
    {
        $user = User::factory()->create();
        $resolver = app(CartResolver::class);
        $request = Request::create('/api/cart', 'GET');
        $request->setUserResolver(fn () => $user);

        $cart = $resolver->resolve($request);

        $this->assertSame($user->id, $cart->user_id);
        $this->assertSame('open', $cart->status);
    }

    public function test_transfer_guest_to_user_merges_items_by_producto_id(): void
    {
        $user = User::factory()->create();
        $resolver = app(CartResolver::class);

        $guestCart = Cart::factory()->forGuest()->create();
        CartItem::factory()->for($guestCart)->create(['producto_id' => 100, 'qty' => 2, 'unit_price' => 50]);
        CartItem::factory()->for($guestCart)->create(['producto_id' => 200, 'qty' => 1, 'unit_price' => 25]);

        $userCart = Cart::factory()->forUser($user)->create();
        CartItem::factory()->for($userCart)->create(['producto_id' => 100, 'qty' => 3, 'unit_price' => 50]);
        CartItem::factory()->for($userCart)->create(['producto_id' => 300, 'qty' => 1, 'unit_price' => 99]);

        $merged = $resolver->transferGuestToUser($guestCart, $user);

        $this->assertSame($userCart->id, $merged->id);
        $this->assertSame(3, $merged->items()->count());
        $item100 = $merged->items()->where('producto_id', 100)->first();
        $this->assertSame(5, (int) $item100->qty);
        $item200 = $merged->items()->where('producto_id', 200)->first();
        $this->assertNotNull($item200);
        $item300 = $merged->items()->where('producto_id', 300)->first();
        $this->assertNotNull($item300);
        $this->assertSame('abandoned', $guestCart->fresh()->status);
    }

    public function test_add_creates_item_when_syscom_returns_product(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/501' => Http::response([
                'producto_id' => 501,
                'titulo' => 'Router Wi-Fi 6',
                'modelo' => 'RB-9000',
                'marca' => 'Mikrotik',
                'img_portada' => 'https://cdn.syscom.mx/rb9000.jpg',
                'total_existencia' => 12,
                'nombre' => 'Router Wi-Fi 6',
                'etiqueta' => null,
                'categorias' => [],
                'garantia' => '12 meses',
                'precios' => ['precio_1' => 199.5, 'precio_descuento' => 0, 'precio_especial' => 0, 'precio_lista' => 220],
            ], 200),
        ]);

        $cart = Cart::factory()->forGuest()->create();
        $service = app(CartService::class);

        $item = $service->add($cart, 501, 1);

        $this->assertSame(501, (int) $item->producto_id);
        $this->assertSame('Router Wi-Fi 6', $item->titulo);
        $this->assertSame(199.5, (float) $item->unit_price);
        $this->assertSame(199.5, (float) $cart->fresh()->subtotal);
    }

    public function test_add_is_idempotent_and_increments_qty(): void
    {
        Http::fake([
            'developers.syscom.mx/oauth/token' => Http::response(['token_type' => 'Bearer', 'expires_in' => 3600, 'access_token' => 'tok']),
            'developers.syscom.mx/api/v1/productos/700' => Http::response($this->fakeProduct(700), 200),
        ]);

        $cart = Cart::factory()->forGuest()->create();
        $service = app(CartService::class);

        $service->add($cart, 700, 2);
        $service->add($cart, 700, 3);

        $cart->refresh();
        $this->assertSame(1, $cart->items()->count());
        $this->assertSame(5, (int) $cart->items()->first()->qty);
    }

    public function test_update_qty_with_zero_removes_item(): void
    {
        $cart = Cart::factory()->forGuest()->create();
        $item = CartItem::factory()->for($cart)->create(['qty' => 2, 'unit_price' => 10]);

        $service = app(CartService::class);
        $service->updateQty($cart, $item->id, 0);

        $this->assertSame(0, $cart->items()->count());
    }

    public function test_update_qty_recomputes_totals(): void
    {
        $cart = Cart::factory()->forGuest()->create();
        $item = CartItem::factory()->for($cart)->create(['qty' => 1, 'unit_price' => 100]);

        $service = app(CartService::class);
        $service->updateQty($cart, $item->id, 4);

        $this->assertSame(400.0, (float) $cart->fresh()->subtotal);
    }

    public function test_remove_item_updates_totals(): void
    {
        $cart = Cart::factory()->forGuest()->create();
        $i1 = CartItem::factory()->for($cart)->create(['qty' => 1, 'unit_price' => 50]);
        $i2 = CartItem::factory()->for($cart)->create(['qty' => 1, 'unit_price' => 75]);

        $service = app(CartService::class);
        $service->remove($cart, $i1->id);

        $cart->refresh();
        $this->assertSame(1, $cart->items()->count());
        $this->assertSame(75.0, (float) $cart->subtotal);
    }

    public function test_clear_empties_cart(): void
    {
        $cart = Cart::factory()->forGuest()->create();
        CartItem::factory()->count(3)->for($cart)->create();
        $cart->refresh();
        $cart->recomputeTotals();

        $service = app(CartService::class);
        $service->clear($cart);

        $fresh = $cart->fresh();
        $this->assertSame(0, $fresh->items()->count());
        $this->assertSame(0.0, (float) $fresh->subtotal);
    }

    public function test_pricing_service_recompute(): void
    {
        $cart = Cart::factory()->forGuest()->create();
        CartItem::factory()->for($cart)->create(['unit_price' => 100, 'qty' => 2]);
        CartItem::factory()->for($cart)->create(['unit_price' => 50, 'qty' => 4]);

        $pricing = app(PricingService::class);
        $cart = $pricing->recompute($cart);

        $this->assertSame(400.0, (float) $cart->subtotal);
    }

    protected function fakeProduct(int $id): array
    {
        return [
            'producto_id' => $id,
            'titulo' => "Producto $id",
            'modelo' => "MDL-$id",
            'marca' => 'TestBrand',
            'img_portada' => null,
            'total_existencia' => 10,
            'nombre' => "Producto $id",
            'etiqueta' => null,
            'categorias' => [],
            'garantia' => null,
            'precios' => ['precio_1' => 10, 'precio_descuento' => 0, 'precio_especial' => 0, 'precio_lista' => 10],
        ];
    }
}
