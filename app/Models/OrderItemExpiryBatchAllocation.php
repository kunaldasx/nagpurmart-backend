<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemExpiryBatchAllocation extends Model
{
    protected $fillable = [
        'order_item_id',
        'expiry_batch_id',
        'quantity_consumed',
        'quantity_restored',
    ];

    protected function casts(): array
    {
        return [
            'quantity_consumed' => 'integer',
            'quantity_restored' => 'integer',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function expiryBatch(): BelongsTo
    {
        return $this->belongsTo(StoreProductExpiryBatch::class, 'expiry_batch_id');
    }
}
