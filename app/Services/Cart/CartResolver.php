<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartResolver
{
    public const COOKIE_NAME = 'cart_token';
    public const HEADER_NAME = 'X-Cart-Token';
    public const COOKIE_MINUTES = 60 * 24 * 30;

    public function resolve(Request $request): Cart
    {
        $user = $request->user();

        if ($user instanceof User) {
            return $this->resolveForUser($user);
        }

        return $this->resolveForGuest($request);
    }

    public function resolveForUser(User $user): Cart
    {
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if ($cart === null) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'session_token' => null,
                'status' => 'open',
            ]);
        }

        return $cart;
    }

    public function resolveForGuest(Request $request): Cart
    {
        $token = $this->extractToken($request);

        if (! is_string($token) || strlen($token) < 32) {
            $token = Str::random(64);
        }

        $cart = Cart::where('session_token', $token)
            ->whereNull('user_id')
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if ($cart === null) {
            $cart = Cart::create([
                'user_id' => null,
                'session_token' => $token,
                'status' => 'open',
            ]);
        }

        return $cart;
    }

    protected function extractToken(Request $request): ?string
    {
        $header = $request->headers->get(self::HEADER_NAME);
        if (is_string($header) && $header !== '' && $this->looksLikeRawToken($header)) {
            return $header;
        }

        $cookie = $request->cookie(self::COOKIE_NAME);
        if (is_string($cookie) && $cookie !== '' && $this->looksLikeRawToken($cookie)) {
            return $cookie;
        }

        return null;
    }

    protected function looksLikeRawToken(string $value): bool
    {
        if (strlen($value) < 32 || strlen($value) > 128) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z0-9]+$/', $value);
    }

    public function attachCookie(Request $request, Cart $cart): void
    {
        if ($cart->user_id !== null) {
            return;
        }

        if (! is_string($cart->session_token)) {
            return;
        }

        if ($request->cookie(self::COOKIE_NAME) === $cart->session_token) {
            return;
        }

        if ($request->headers->get(self::HEADER_NAME) === $cart->session_token) {
            return;
        }

        cookie()->queue(
            self::COOKIE_NAME,
            $cart->session_token,
            self::COOKIE_MINUTES,
            '/',
            null,
            false,
            true,
        );
    }

    public function transferGuestToUser(Cart $guestCart, User $user): Cart
    {
        $userCart = $this->resolveForUser($user);

        foreach ($guestCart->items()->get() as $guestItem) {
            $existing = $userCart->items()
                ->where('producto_id', $guestItem->producto_id)
                ->first();

            if ($existing === null) {
                $guestItem->update(['cart_id' => $userCart->id]);
                continue;
            }

            $existing->qty = (int) $existing->qty + (int) $guestItem->qty;
            $existing->line_total = round(((float) $existing->unit_price) * $existing->qty, 2);
            $existing->save();
            $guestItem->delete();
        }

        $guestCart->update(['status' => 'abandoned']);
        $userCart->refresh()->recomputeTotals();

        return $userCart->fresh('items');
    }
}
