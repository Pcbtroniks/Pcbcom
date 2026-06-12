<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cart = $this->resource;

        return [
            'id' => $cart->id,
            'status' => $cart->status,
            'currency' => $cart->currency,
            'subtotal' => (float) $cart->subtotal,
            'shipping' => (float) $cart->shipping,
            'tax' => (float) $cart->tax,
            'total' => (float) $cart->total,
            'item_count' => $cart->items->sum('qty'),
            'last_synced_at' => optional($cart->last_synced_at)?->toIso8601String(),
            'items' => $cart->items->map(fn ($item) => [
                'id' => $item->id,
                'producto_id' => (int) $item->producto_id,
                'sku' => $item->sku,
                'titulo' => $item->titulo,
                'modelo' => $item->modelo,
                'marca' => $item->marca,
                'img_portada' => $item->img_portada,
                'unit_price' => (float) $item->unit_price,
                'qty' => (int) $item->qty,
                'line_total' => (float) $item->line_total,
                'notes' => $item->notes,
            ])->values(),
        ];
    }
}
