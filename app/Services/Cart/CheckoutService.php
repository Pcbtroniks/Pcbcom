<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Payment\PaymentGateway;
use App\Services\Payment\PaymentResult;
use App\Services\Syscom\CartCheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected PricingService $pricing,
        protected CartCheckoutService $syscom,
        protected PaymentGateway $gateway,
    ) {}

    public function preview(Cart $cart): Cart
    {
        return $this->pricing->recompute($cart);
    }

    public function confirm(Cart $cart, array $data, ?User $user = null, ?string $idempotencyKey = null): Order
    {
        $idempotencyKey ??= 'ck_'.Str::random(24);

        if ($existing = $this->findIdempotent($idempotencyKey)) {
            return $existing;
        }

        if ($cart->items()->count() === 0) {
            throw new \RuntimeException('El carrito está vacío.');
        }

        return DB::transaction(function () use ($cart, $data, $user, $idempotencyKey) {
            $cart = $this->pricing->recompute($cart);
            $cart->update(['status' => 'checkout']);

            $syscomCartId = $this->syscom->createOrder($cart, $data);
            $syscomResponse = null;
            if ($syscomCartId !== null) {
                $syscomResponse = $this->syscom->confirmOrder($syscomCartId);
            }

            $order = Order::create([
                'number' => $this->generateOrderNumber(),
                'user_id' => $cart->user_id ?? $user?->id,
                'cart_id' => $cart->id,
                'syscom_order_id' => $syscomCartId,
                'status' => 'pending',
                'shipping_address' => $data['shipping_address'] ?? [],
                'billing_address' => $data['billing_address'] ?? $data['shipping_address'] ?? [],
                'currency' => $cart->currency,
                'subtotal' => $cart->subtotal,
                'shipping' => $cart->shipping,
                'tax' => $cart->tax,
                'total' => $cart->total,
                'payment_method' => $data['payment_method'] ?? $this->gateway->name(),
                'notes' => $data['notes'] ?? null,
                'syscom_response' => $syscomResponse,
                'placed_at' => now(),
                'meta' => ['idempotency_key' => $idempotencyKey],
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($cart->items()->get() as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'producto_id' => $item->producto_id,
                    'sku' => $item->sku,
                    'titulo' => $item->titulo,
                    'modelo' => $item->modelo,
                    'marca' => $item->marca,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'snapshot' => $item->snapshot,
                ]);
            }

            $this->chargePayment($order, $data);

            $cart->update(['status' => 'converted']);
            $cart->items()->delete();

            return $order->fresh(['items']);
        });
    }

    public function markPaid(Order $order, string $paymentIntentId, ?string $status = null): Order
    {
        $order->markPaid($paymentIntentId, $status);

        if (in_array($order->status, ['paid', 'processing'], true)) {
            $order->update(['payment_status' => $status ?? 'succeeded']);
        }

        return $order->fresh();
    }

    public function findIdempotent(string $key): ?Order
    {
        if ($key === '') {
            return null;
        }

        return Order::query()
            ->where('idempotency_key', $key)
            ->latest()
            ->first();
    }

    protected function chargePayment(Order $order, array $data): void
    {
        try {
            $result = $this->gateway->charge($order, $data);

            $order->update([
                'payment_intent_id' => $result->paymentIntentId,
                'payment_status' => $result->status,
            ]);

            $shouldAutoComplete = $this->gateway->name() === 'null'
                && (bool) config('payment.auto_complete', true);

            if ($result->status === 'succeeded' || $shouldAutoComplete) {
                $order->update(['status' => 'paid', 'paid_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::error('Checkout charge failed', [
                'order' => $order->number,
                'error' => $e->getMessage(),
            ]);
            $order->update(['payment_status' => 'failed', 'notes' => trim(($order->notes ?? '')."\n[payment] ".$e->getMessage())]);
        }
    }

    protected function generateOrderNumber(): string
    {
        $year = now()->format('Y');
        do {
            $random = strtoupper(Str::random(6));
            $number = "PCB-{$year}-{$random}";
        } while (Order::where('number', $number)->exists());

        return $number;
    }
}
