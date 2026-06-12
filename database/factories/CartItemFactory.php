<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        $unit = $this->faker->randomFloat(2, 10, 1500);
        $qty = $this->faker->numberBetween(1, 5);

        return [
            'cart_id' => Cart::factory(),
            'producto_id' => $this->faker->unique()->numberBetween(1, 999999),
            'sku' => strtoupper($this->faker->bothify('SKU-####-???')),
            'titulo' => ucfirst($this->faker->words(3, true)),
            'modelo' => strtoupper($this->faker->bothify('MDL-####')),
            'marca' => $this->faker->randomElement(['Ubiquiti', 'Mikrotik', 'TP-Link', 'Cisco', 'Hikvision']),
            'img_portada' => 'https://picsum.photos/seed/'.$this->faker->word().'/400/400',
            'unit_price' => $unit,
            'qty' => $qty,
            'line_total' => round($unit * $qty, 2),
            'snapshot' => ['origen' => 'factory'],
            'notes' => null,
        ];
    }
}
