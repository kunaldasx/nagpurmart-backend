<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreProductExpiryBatch extends Model
{
    protected $fillable = [
        'store_product_variant_id',
        'batch_code',
        'quantity_received',
        'quantity_remaining',
        'expiry_date',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'integer',
            'quantity_remaining' => 'integer',
            'expiry_date' => 'date',
        ];
    }

    public function storeProductVariant(): BelongsTo
    {
        return $this->belongsTo(StoreProductVariant::class)->withTrashed();
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(OrderItemExpiryBatchAllocation::class, 'expiry_batch_id');
    }
}
