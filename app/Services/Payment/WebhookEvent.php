<?php

namespace App\Services\Payment;

class WebhookEvent
{
    public function __construct(
        public readonly string $type,
        public readonly string $paymentIntentId,
        public readonly string $status,
        public readonly array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? 'unknown'),
            paymentIntentId: (string) ($data['payment_intent_id'] ?? $data['id'] ?? ''),
            status: (string) ($data['status'] ?? 'unknown'),
            raw: $data,
        );
    }
}
