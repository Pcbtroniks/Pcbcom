<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StripeGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'stripe';
    }

    public function charge(Order $order, array $options = []): PaymentResult
    {
        $secret = (string) config('payment.methods.stripe.secret_key');
        if ($secret === '') {
            return PaymentResult::failed('Stripe secret key no configurada.');
        }

        $currency = (string) config('payment.methods.stripe.currency', 'usd');
        $amount = (int) round(((float) $order->total) * 100);

        try {
            $response = Http::withToken($secret)
                ->asForm()
                ->post('https://api.stripe.com/v1/payment_intents', [
                    'amount' => $amount,
                    'currency' => $currency,
                    'description' => "Orden {$order->number}",
                    'metadata' => [
                        'order_number' => $order->number,
                        'order_id' => (string) $order->id,
                    ],
                ]);

            if ($response->failed()) {
                return PaymentResult::failed(
                    'Stripe rechazó la solicitud: '.$response->body(),
                    $response->json() ?? [],
                );
            }

            $data = $response->json();
            return new PaymentResult(
                success: true,
                status: 'pending',
                paymentIntentId: (string) ($data['id'] ?? 'pi_'.Str::random(24)),
                redirectUrl: $data['client_secret'] ?? null,
                raw: $data,
            );
        } catch (\Throwable $e) {
            Log::error('Stripe charge failed', [
                'order' => $order->number,
                'error' => $e->getMessage(),
            ]);
            return PaymentResult::failed('Error de comunicación con Stripe: '.$e->getMessage());
        }
    }

    public function retrieve(string $paymentIntentId): PaymentResult
    {
        $secret = (string) config('payment.methods.stripe.secret_key');
        if ($secret === '') {
            return PaymentResult::failed('Stripe secret key no configurada.');
        }

        try {
            $response = Http::withToken($secret)->get("https://api.stripe.com/v1/payment_intents/{$paymentIntentId}");

            if ($response->failed()) {
                return PaymentResult::failed('Stripe retrieve failed', $response->json() ?? []);
            }

            $data = $response->json();
            $status = (string) ($data['status'] ?? 'unknown');

            return new PaymentResult(
                success: in_array($status, ['succeeded', 'processing', 'requires_capture'], true),
                status: $status,
                paymentIntentId: $paymentIntentId,
                raw: $data,
            );
        } catch (\Throwable $e) {
            return PaymentResult::failed('Stripe retrieve error: '.$e->getMessage());
        }
    }

    public function handleWebhook(array $payload, ?string $signature = null): WebhookEvent
    {
        $secret = (string) config('payment.methods.stripe.webhook_secret');
        $type = (string) ($payload['type'] ?? 'unknown');

        $intent = $payload['data']['object'] ?? $payload;
        $paymentIntentId = (string) ($intent['id'] ?? $intent['payment_intent'] ?? '');
        $status = (string) ($intent['status'] ?? 'unknown');

        if ($secret !== '' && is_string($signature)) {
            $expected = hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE), $secret);
            if (! hash_equals($expected, $signature)) {
                Log::warning('Stripe webhook signature mismatch');
            }
        }

        return new WebhookEvent(
            type: $type,
            paymentIntentId: $paymentIntentId,
            status: $status,
            raw: $payload,
        );
    }
}
