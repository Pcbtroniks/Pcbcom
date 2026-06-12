<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_factory_creates_open_cart_for_user(): void
    {
        $user = User::factory()->create();

        $cart = Cart::factory()->forUser($user)->create();

        $this->assertSame('open', $cart->status);
        $this->assertSame($user->id, $cart->user_id);
        $this->assertNull($cart->session_token);
        $this->assertTrue($cart->isOpen());
    }

    public function test_cart_items_recompute_subtotal_on_save(): void
    {
        $cart = Cart::factory()->forUser(User::factory()->create())->create();

        CartItem::factory()->for($cart)->create([
            'unit_price' => 100.00,
            'qty' => 2,
        ]);
        CartItem::factory()->for($cart)->create([
            'unit_price' => 50.50,
            'qty' => 3,
        ]);

        $cart->refresh();

        $this->assertSame(351.50, (float) $cart->subtotal);
        $this->assertSame(351.50, (float) $cart->total);
    }

    public function test_cart_item_line_total_is_recalculated_on_save(): void
    {
        $cart = Cart::factory()->forUser(User::factory()->create())->create();

        $item = CartItem::factory()->for($cart)->create([
            'unit_price' => 199.99,
            'qty' => 4,
        ]);

        $this->assertSame(799.96, (float) $item->fresh()->line_total);
    }

    public function test_user_open_cart_helper_returns_latest_open_cart(): void
    {
        $user = User::factory()->create();

        $converted = Cart::factory()->forUser($user)->converted()->create();
        $latest = Cart::factory()->forUser($user)->create();

        $this->assertSame($latest->id, $user->openCart()->id);
        $this->assertNotSame($converted->id, $user->openCart()->id);
    }

    public function test_order_factory_paid_state_marks_paid_at(): void
    {
        $order = Order::factory()->paid()->create();

        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertTrue($order->isPaid());
    }

    public function test_order_mark_paid_sets_status_and_timestamp(): void
    {
        $order = Order::factory()->create(['status' => 'pending', 'paid_at' => null]);

        $order->markPaid('pi_test_123', 'succeeded');

        $fresh = $order->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertSame('pi_test_123', $fresh->payment_intent_id);
        $this->assertSame('succeeded', $fresh->payment_status);
        $this->assertNotNull($fresh->paid_at);
    }
}
