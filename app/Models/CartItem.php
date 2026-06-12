<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'producto_id',
        'sku',
        'titulo',
        'modelo',
        'marca',
        'img_portada',
        'unit_price',
        'qty',
        'line_total',
        'snapshot',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'qty' => 'integer',
        'snapshot' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (CartItem $item): void {
            $item->line_total = round(((float) $item->unit_price) * ((int) $item->qty), 2);
        });

        static::saved(function (CartItem $item): void {
            $item->cart?->recomputeTotals();
        });

        static::deleted(function (CartItem $item): void {
            $item->cart?->recomputeTotals();
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}
