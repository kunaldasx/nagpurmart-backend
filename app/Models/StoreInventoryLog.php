<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreInventoryLog extends Model
{
    public $timestamps = false; // only has created_at

    protected $fillable = [
        'store_id',
        'product_variant_id',
        'change_type',
        'quantity',
        'reason',
        'created_at'
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}

