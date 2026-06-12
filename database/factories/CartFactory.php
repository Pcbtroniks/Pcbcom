<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'session_token' => Str::random(64),
            'syscom_wishlist_id' => null,
            'status' => 'open',
            'currency' => 'USD',
            'subtotal' => 0,
            'shipping' => 0,
            'tax' => 0,
            'total' => 0,
            'last_synced_at' => null,
        ];
    }

    public function forGuest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'session_token' => Str::random(64),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id, 'session_token' => null]);
    }

    public function checkout(): static
    {
        return $this->state(fn () => ['status' => 'checkout']);
    }

    public function converted(): static
    {
        return $this->state(fn () => ['status' => 'converted']);
    }
}
