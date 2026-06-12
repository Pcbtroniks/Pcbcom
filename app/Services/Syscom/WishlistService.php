<?php

namespace App\Services\Syscom;

use App\Models\Cart;
use Illuminate\Support\Facades\Log;
use Throwable;

class WishlistService
{
    public function __construct(protected SyscomHttpClient $client) {}

    public function isEnabled(): bool
    {
        return (bool) config('syscom.wishlist.enabled', true);
    }

    public function ensureWishlist(Cart $cart): ?string
    {
        if (! $this->isEnabled()) {
            return $cart->syscom_wishlist_id;
        }

        if (! empty($cart->syscom_wishlist_id)) {
            return $cart->syscom_wishlist_id;
        }

        try {
            $response = $this->client->post('wishlist', [
                'producto_id' => null,
                'origen' => 'pcbecom',
            ]);

            $wishlistId = $response['id'] ?? $response['wishlist_id'] ?? null;

            if (is_string($wishlistId) && $wishlistId !== '') {
                $cart->syscom_wishlist_id = $wishlistId;
                $cart->save();
                return $wishlistId;
            }
        } catch (Throwable $e) {
            Log::warning('Syscom wishlist ensure failed', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public function addItem(Cart $cart, int $productoId): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $wishlistId = $this->ensureWishlist($cart);
        if ($wishlistId === null) {
            return false;
        }

        try {
            $this->client->post("wishlist/{$wishlistId}/items", [
                'producto_id' => $productoId,
            ]);
            return true;
        } catch (Throwable $e) {
            Log::warning('Syscom wishlist add item failed', [
                'cart_id' => $cart->id,
                'wishlist_id' => $wishlistId,
                'producto_id' => $productoId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function removeItem(Cart $cart, int $productoId): bool
    {
        if (! $this->isEnabled() || empty($cart->syscom_wishlist_id)) {
            return false;
        }

        try {
            $this->client->delete("wishlist/{$cart->syscom_wishlist_id}/items/{$productoId}");
            return true;
        } catch (Throwable $e) {
            Log::warning('Syscom wishlist remove item failed', [
                'cart_id' => $cart->id,
                'producto_id' => $productoId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function fetchItems(Cart $cart): array
    {
        if (! $this->isEnabled() || empty($cart->syscom_wishlist_id)) {
            return [];
        }

        try {
            $response = $this->client->get("wishlist/{$cart->syscom_wishlist_id}/items");
            return is_array($response) ? $response : [];
        } catch (Throwable $e) {
            Log::warning('Syscom wishlist fetch items failed', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
