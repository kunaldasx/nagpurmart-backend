<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bag extends Model
{
    protected $fillable = ['seller_id', 'barcode', 'seller_order_id', 'assigned_at'];

    protected $casts = ['assigned_at' => 'datetime'];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }
}