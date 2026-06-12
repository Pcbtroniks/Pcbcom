<?php

namespace App\Services\Syscom;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Throwable;

class CartCheckoutService
{
    public function __construct(protected SyscomHttpClient $client) {}

    public function isEnabled(): bool
    {
        return (bool) config('syscom.cart_checkout.enabled', true);
    }

    public function createOrder(Cart $cart, array $payload = []): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        try {
            $response = $this->client->post('carrito', $this->buildPayload($cart, $payload));
            return (string) ($response['id'] ?? $response['order_id'] ?? null) ?: null;
        } catch (Throwable $e) {
            Log::warning('Syscom cart create order failed', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function confirmOrder(string $syscomCartId): ?array
    {
        if (! $this->isEnabled() || $syscomCartId === '') {
            return null;
        }

        try {
            return $this->client->post("carrito/{$syscomCartId}/confirmar");
        } catch (Throwable $e) {
            Log::warning('Syscom cart confirm failed', [
                'syscom_cart_id' => $syscomCartId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function fetchStatus(string $syscomOrderId): ?array
    {
        if (! $this->isEnabled() || $syscomOrderId === '') {
            return null;
        }

        try {
            return $this->client->get("pedidos/{$syscomOrderId}");
        } catch (Throwable $e) {
            Log::warning('Syscom order status fetch failed', [
                'syscom_order_id' => $syscomOrderId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function buildPayload(Cart $cart, array $overrides): array
    {
        $cart->loadMissing('items');

        $lines = $cart->items->map(fn ($item) => [
            'producto_id' => (int) $item->producto_id,
            'cantidad' => (int) $item->qty,
            'precio_unitario' => (float) $item->unit_price,
        ])->all();

        return array_merge([
            'origen' => 'pcbecom',
            'moneda' => $cart->currency,
            'lineas' => $lines,
            'subtotal' => (float) $cart->subtotal,
            'envio' => (float) $cart->shipping,
            'impuestos' => (float) $cart->tax,
            'total' => (float) $cart->total,
            'cliente' => $cart->user_id ? ['id' => $cart->user_id] : null,
        ], $overrides);
    }
}
