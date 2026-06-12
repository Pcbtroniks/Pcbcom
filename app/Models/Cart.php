<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'syscom_wishlist_id',
        'status',
        'currency',
        'subtotal',
        'shipping',
        'tax',
        'total',
        'last_synced_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function order(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function itemCount(): int
    {
        return (int) $this->items()->sum('qty');
    }

    public function recomputeTotals(): void
    {
        $this->subtotal = (float) $this->items()->sum('line_total');
        $this->total = (float) $this->subtotal + (float) $this->shipping + (float) $this->tax;
        $this->save();
    }
}
