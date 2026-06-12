<?php

namespace App\Services\Payment;

use App\Models\Order;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $paymentIntentId = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $message = null,
        public readonly array $raw = [],
    ) {}

    public static function pending(?Order $order = null, string $paymentIntentId = '', ?string $redirectUrl = null, array $raw = []): self
    {
        return new self(
            success: true,
            status: 'pending',
            paymentIntentId: $paymentIntentId,
            redirectUrl: $redirectUrl,
            raw: $raw,
        );
    }

    public static function succeeded(string $paymentIntentId, array $raw = []): self
    {
        return new self(
            success: true,
            status: 'succeeded',
            paymentIntentId: $paymentIntentId,
            raw: $raw,
        );
    }

    public static function failed(string $message, array $raw = []): self
    {
        return new self(
            success: false,
            status: 'failed',
            message: $message,
            raw: $raw,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'payment_intent_id' => $this->paymentIntentId,
            'redirect_url' => $this->redirectUrl,
            'message' => $this->message,
        ];
    }
}
