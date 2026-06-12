<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Cart\CheckoutService;
use App\Services\Payment\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected CheckoutService $checkout,
        protected PaymentGateway $gateway,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('Stripe-Signature') ?? $request->header('X-Signature');

        try {
            $event = $this->gateway->handleWebhook($payload, $signature);
        } catch (\Throwable $e) {
            Log::error('Webhook parse error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'invalid_payload'], 400);
        }

        $order = Order::where('payment_intent_id', $event->paymentIntentId)->first();
        if ($order === null) {
            Log::warning('Webhook for unknown payment intent', [
                'payment_intent_id' => $event->paymentIntentId,
                'type' => $event->type,
            ]);
            return response()->json(['received' => true, 'matched' => false]);
        }

        match ($event->status) {
            'succeeded', 'paid', 'received' => $this->checkout->markPaid($order, $event->paymentIntentId, $event->status),
            'failed', 'canceled', 'voided' => $order->update(['payment_status' => $event->status, 'status' => 'cancelled']),
            'processing', 'pending', 'awaiting_transfer' => $order->update(['payment_status' => $event->status]),
            default => Log::info('Unhandled webhook status', ['status' => $event->status]),
        };

        return response()->json(['received' => true, 'order' => $order->number]);
    }
}
