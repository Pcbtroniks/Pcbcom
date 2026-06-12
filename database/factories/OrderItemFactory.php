<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $unit = $this->faker->randomFloat(2, 10, 1500);
        $qty = $this->faker->numberBetween(1, 5);

        return [
            'order_id' => Order::factory(),
            'producto_id' => $this->faker->unique()->numberBetween(1, 999999),
            'sku' => strtoupper($this->faker->bothify('SKU-####-???')),
            'titulo' => ucfirst($this->faker->words(3, true)),
            'modelo' => strtoupper($this->faker->bothify('MDL-####')),
            'marca' => $this->faker->randomElement(['Ubiquiti', 'Mikrotik', 'TP-Link', 'Cisco', 'Hikvision']),
            'qty' => $qty,
            'unit_price' => $unit,
            'line_total' => round($unit * $qty, 2),
            'snapshot' => ['origen' => 'factory'],
        ];
    }
}
