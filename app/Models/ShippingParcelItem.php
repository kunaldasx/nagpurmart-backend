<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingParcelItem extends Model
{
    protected $fillable = [
        'parcel_id',
        'order_item_id',
        'quantity_shipped',
    ];

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(ShippingParcel::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
