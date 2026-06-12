<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $cart = Cart::factory()
            ->forUser($user)
            ->create(['status' => 'open']);

        CartItem::factory()
            ->count(3)
            ->for($cart)
            ->create();

        $cart->recomputeTotals();

        $order = Order::factory()
            ->paid()
            ->for($user)
            ->create();

        OrderItem::factory()
            ->count(3)
            ->for($order)
            ->create();
    }
}
