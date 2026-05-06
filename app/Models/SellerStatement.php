<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerStatement extends Model
{

    protected $fillable = [
        'seller_id',
        'order_id',
        'order_item_id',
        'return_id',
        'entry_type',
        'amount',
        'currency_code',
        'reference_type',
        'reference_id',
        'description',
        'meta',
        'posted_at',
        'settlement_status',
        'settled_at',
        'settlement_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
        'posted_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderItemReturn::class, 'return_id');
    }
}
