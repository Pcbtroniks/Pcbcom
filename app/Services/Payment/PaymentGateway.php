<?php

namespace App\Services\Payment;

use App\Models\Order;

interface PaymentGateway
{
    public function name(): string;

    public function charge(Order $order, array $options = []): PaymentResult;

    public function retrieve(string $paymentIntentId): PaymentResult;

    public function handleWebhook(array $payload, ?string $signature = null): WebhookEvent;
}
