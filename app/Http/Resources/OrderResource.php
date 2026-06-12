<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->resource;
        $itemsLoaded = $order->relationLoaded('items');

        $items = $itemsLoaded
            ? $order->items->map(fn ($item) => [
                'id' => $item->id,
                'producto_id' => (int) $item->producto_id,
                'sku' => $item->sku,
                'titulo' => $item->titulo,
                'modelo' => $item->modelo,
                'marca' => $item->marca,
                'qty' => (int) $item->qty,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ])->values()
            : null;

        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'currency' => $order->currency,
            'subtotal' => (float) $order->subtotal,
            'shipping' => (float) $order->shipping,
            'tax' => (float) $order->tax,
            'total' => (float) $order->total,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment_intent_id' => $order->payment_intent_id,
            'shipping_address' => $order->shipping_address,
            'billing_address' => $order->billing_address,
            'tracking_number' => $order->tracking_number,
            'carrier' => $order->carrier,
            'syscom_order_id' => $order->syscom_order_id,
            'placed_at' => optional($order->placed_at)?->toIso8601String(),
            'paid_at' => optional($order->paid_at)?->toIso8601String(),
            'shipped_at' => optional($order->shipped_at)?->toIso8601String(),
            'delivered_at' => optional($order->delivered_at)?->toIso8601String(),
            'items' => $items,
        ];
    }
}
