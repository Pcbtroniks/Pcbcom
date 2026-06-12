<?php

namespace App\Listeners;

use App\Services\Cart\CartResolver;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MergeGuestCart
{
    public function __construct(protected CartResolver $resolver) {}

    public function handle(Login $event): void
    {
        $request = request();
        $token = $request?->cookie(CartResolver::COOKIE_NAME);

        if (! is_string($token) || $token === '') {
            return;
        }

        $guestCart = \App\Models\Cart::where('session_token', $token)
            ->whereNull('user_id')
            ->where('status', 'open')
            ->first();

        if ($guestCart === null) {
            return;
        }

        try {
            $this->resolver->transferGuestToUser($guestCart, $event->user);
        } catch (\Throwable $e) {
            Log::error('Guest cart merge failed', [
                'user_id' => $event->user->id,
                'guest_cart_id' => $guestCart->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
