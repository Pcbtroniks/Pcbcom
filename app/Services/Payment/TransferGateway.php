<?php

namespace App\Services\Payment;

use App\Models\Order;

class TransferGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'transfer';
    }

    public function charge(Order $order, array $options = []): PaymentResult
    {
        $clabe = (string) config('payment.methods.transfer.clabe');
        $bank = (string) config('payment.methods.transfer.bank');
        $prefix = (string) config('payment.methods.transfer.reference_prefix', 'PCB-');

        return new PaymentResult(
            success: true,
            status: 'awaiting_transfer',
            paymentIntentId: 'transfer_'.$order->number,
            redirectUrl: null,
            message: "Transferir a {$bank} CLABE {$clabe} con referencia {$prefix}{$order->number}",
            raw: [
                'gateway' => 'transfer',
                'clabe' => $clabe,
                'bank' => $bank,
                'reference' => $prefix.$order->number,
                'amount' => (float) $order->total,
                'currency' => $order->currency,
            ],
        );
    }

    public function retrieve(string $paymentIntentId): PaymentResult
    {
        return new PaymentResult(
            success: true,
            status: 'awaiting_transfer',
            paymentIntentId: $paymentIntentId,
        );
    }

    public function handleWebhook(array $payload, ?string $signature = null): WebhookEvent
    {
        return new WebhookEvent(
            type: 'transfer.received',
            paymentIntentId: (string) ($payload['payment_intent_id'] ?? ''),
            status: (string) ($payload['status'] ?? 'succeeded'),
            raw: $payload,
        );
    }
}
