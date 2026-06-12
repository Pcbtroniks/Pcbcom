<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'user_id',
        'cart_id',
        'syscom_order_id',
        'status',
        'shipping_address',
        'billing_address',
        'currency',
        'subtotal',
        'shipping',
        'tax',
        'total',
        'payment_method',
        'payment_intent_id',
        'payment_status',
        'tracking_number',
        'carrier',
        'syscom_response',
        'notes',
        'meta',
        'idempotency_key',
        'placed_at',
        'paid_at',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'syscom_response' => 'array',
        'meta' => 'array',
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'placed_at' => 'datetime',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'shipped', 'delivered'], true);
    }

    public function markPaid(?string $paymentIntentId = null, ?string $paymentStatus = null): void
    {
        $this->forceFill([
            'status' => 'paid',
            'payment_intent_id' => $paymentIntentId ?? $this->payment_intent_id,
            'payment_status' => $paymentStatus ?? $this->payment_status,
            'paid_at' => $this->paid_at ?? now(),
        ])->save();
    }
}
