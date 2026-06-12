<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Jobs\SyncCartWithSyscom;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function __construct(
        protected CartResolver $resolver,
        protected CartService $service,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartForRequest($request)->load('items');

        return (new CartResource($cart))
            ->response()
            ->setStatusCode(200);
    }

    public function store(AddToCartRequest $request): JsonResponse
    {
        $cart = $this->cartForRequest($request);
        $this->resolver->attachCookie($request, $cart);

        try {
            $item = $this->service->add(
                $cart,
                (int) $request->input('producto_id'),
                (int) $request->input('qty', 1),
                $request->filled('unit_price') ? (float) $request->input('unit_price') : null,
            );
        } catch (\Throwable $e) {
            Log::error('Add to cart failed', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'No se pudo agregar el producto al carrito.',
                'detail' => $e->getMessage(),
            ], 422);
        }

        $cart->load('items');

        return (new CartResource($cart))
            ->additional(['added' => ['item_id' => $item->id, 'producto_id' => (int) $item->producto_id]])
            ->response();
    }

    public function update(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        $cart = $this->cartForRequest($request);
        $this->resolver->attachCookie($request, $cart);

        try {
            $this->service->updateQty($cart, $itemId, (int) $request->input('qty'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'Item no encontrado'], 404);
        }

        $cart->load('items');

        return (new CartResource($cart))->response();
    }

    public function destroy(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->cartForRequest($request);
        $this->resolver->attachCookie($request, $cart);

        try {
            $this->service->remove($cart, $itemId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'Item no encontrado'], 404);
        }

        $cart->load('items');

        return (new CartResource($cart))->response();
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartForRequest($request);
        $this->resolver->attachCookie($request, $cart);

        $this->service->clear($cart);

        return (new CartResource($cart->fresh('items')))->response();
    }

    public function sync(Request $request): JsonResponse
    {
        $cart = $this->cartForRequest($request);
        $this->resolver->attachCookie($request, $cart);

        $useQueue = (bool) $request->boolean('async', false);

        if ($useQueue) {
            SyncCartWithSyscom::dispatch($cart->id);
            return response()->json([
                'queued' => true,
                'cart_id' => $cart->id,
            ]);
        }

        $cart = $this->service->syncWithSyscom($cart);

        return (new CartResource($cart->load('items')))->response();
    }

    protected function cartForRequest(Request $request): \App\Models\Cart
    {
        $cart = $request->attributes->get('cart');
        if ($cart instanceof \App\Models\Cart) {
            return $cart;
        }
        return $this->resolver->resolve($request);
    }
}
