<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Support\Str;

class NullGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'null';
    }

    public function charge(Order $order, array $options = []): PaymentResult
    {
        $intent = 'pi_null_'.Str::random(24);

        return new PaymentResult(
            success: true,
            status: 'pending',
            paymentIntentId: $intent,
            redirectUrl: null,
            raw: ['gateway' => 'null', 'auto_complete' => (bool) config('payment.auto_complete', true)],
        );
    }

    public function retrieve(string $paymentIntentId): PaymentResult
    {
        return PaymentResult::succeeded($paymentIntentId, ['gateway' => 'null']);
    }

    public function handleWebhook(array $payload, ?string $signature = null): WebhookEvent
    {
        $object = $payload['data']['object'] ?? $payload;
        return WebhookEvent::fromArray([
            'type' => $payload['type'] ?? 'null.test',
            'payment_intent_id' => (string) ($object['id'] ?? $object['payment_intent'] ?? $payload['payment_intent_id'] ?? ''),
            'status' => (string) ($object['status'] ?? $payload['status'] ?? 'succeeded'),
            'raw' => $payload,
        ]);
    }
}
