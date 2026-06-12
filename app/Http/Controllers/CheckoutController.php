<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartResolver $resolver,
        protected CheckoutService $checkout,
    ) {}

    public function preview(Request $request): JsonResponse
    {
        $cart = $this->cartForRequest($request);
        $this->resolver->attachCookie($request, $cart);

        if ($cart->items()->count() === 0) {
            return response()->json(['error' => 'El carrito está vacío.'], 422);
        }

        $cart = $this->checkout->preview($cart);

        return (new CartResource($cart->load('items')))
            ->response()
            ->setStatusCode(200);
    }

    public function confirm(CheckoutRequest $request): JsonResponse
    {
        $cart = $this->cartForRequest($request);
        $this->resolver->attachCookie($request, $cart);

        try {
            $order = $this->checkout->confirm(
                cart: $cart,
                data: $request->validated(),
                user: $request->user(),
                idempotencyKey: $request->input('idempotency_key'),
            );
        } catch (\Throwable $e) {
            Log::error('Checkout confirm failed', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'error' => 'No se pudo confirmar el pedido.',
                'detail' => $e->getMessage(),
            ], 422);
        }

        return (new OrderResource($order->load('items')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $number): JsonResponse
    {
        $order = Order::where('number', $number)
            ->when($request->user(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('items')
            ->firstOrFail();

        return (new OrderResource($order))->response();
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
