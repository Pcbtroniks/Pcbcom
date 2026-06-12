<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $shipping = $this->faker->randomFloat(2, 0, 250);
        $tax = round($subtotal * 0.16, 2);

        return [
            'number' => 'PCB-'.now()->format('Y').'-'.$this->faker->unique()->numberBetween(1000, 999999),
            'user_id' => User::factory(),
            'cart_id' => Cart::factory(),
            'syscom_order_id' => null,
            'status' => 'pending',
            'shipping_address' => [
                'name' => $this->faker->name(),
                'phone' => $this->faker->e164PhoneNumber(),
                'line1' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'zip' => $this->faker->postcode(),
                'country' => 'MX',
            ],
            'billing_address' => null,
            'currency' => 'USD',
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => round($subtotal + $shipping + $tax, 2),
            'payment_method' => null,
            'payment_intent_id' => null,
            'payment_status' => null,
            'tracking_number' => null,
            'carrier' => null,
            'syscom_response' => null,
            'notes' => null,
            'placed_at' => now(),
            'paid_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => 'stripe',
            'payment_intent_id' => 'pi_'.$this->faker->bothify('????????????????'),
            'payment_status' => 'succeeded',
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn () => [
            'status' => 'shipped',
            'shipped_at' => now(),
            'tracking_number' => strtoupper($this->faker->bothify('TRK##########')),
            'carrier' => $this->faker->randomElement(['FedEx', 'DHL', 'Estafeta']),
        ]);
    }
}
