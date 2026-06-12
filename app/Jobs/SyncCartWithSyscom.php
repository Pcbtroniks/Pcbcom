<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Services\Cart\CartService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCartWithSyscom implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(public int $cartId) {}

    public function handle(CartService $service): void
    {
        $cart = Cart::with('items')->find($this->cartId);
        if ($cart === null) {
            return;
        }

        $service->syncWithSyscom($cart);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SyncCartWithSyscom permanently failed', [
            'cart_id' => $this->cartId,
            'error' => $e->getMessage(),
        ]);
    }
}
