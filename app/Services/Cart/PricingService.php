<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Services\Syscom\DTOs\PriceDto;
use App\Services\Syscom\ProductsService;

class PricingService
{
    public function __construct(protected ProductsService $products) {}

    public function quoteForProduct(int $productoId, ?PriceDto $hint = null): array
    {
        $product = $this->products->getProductById($productoId);

        $priceDto = $hint ?? ($product['precios'] ?? null)
            ? PriceDto::fromArray($product['precios'] ?? [])
            : null;

        if ($priceDto === null) {
            return [
                'unit_price' => 0.0,
                'price_source' => 'unknown',
            ];
        }

        return [
            'unit_price' => $priceDto->effective(),
            'price_source' => 'syscom',
        ];
    }

    public function computeShipping(float $subtotal): float
    {
        $threshold = (float) config('payment.shipping.free_threshold', 500.0);
        $flat = (float) config('payment.shipping.flat', 99.0);

        if ($subtotal <= 0) {
            return 0.0;
        }

        return $subtotal >= $threshold ? 0.0 : $flat;
    }

    public function computeTax(float $subtotal): float
    {
        $rate = (float) config('payment.tax_rate', 0.16);
        return round($subtotal * $rate, 2);
    }

    public function recompute(Cart $cart): Cart
    {
        $cart->loadMissing('items');

        $subtotal = 0.0;
        foreach ($cart->items as $item) {
            $subtotal += (float) $item->line_total;
        }

        $subtotal = round($subtotal, 2);
        $shipping = $this->computeShipping($subtotal);
        $tax = $this->computeTax($subtotal);

        $cart->subtotal = $subtotal;
        $cart->shipping = $shipping;
        $cart->tax = $tax;
        $cart->total = round($subtotal + $shipping + $tax, 2);
        $cart->save();

        return $cart;
    }
}
