<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingParcel extends Model
{
    protected $fillable = [
        'order_id',
        'store_id',
        'delivery_boy_id',
        'shipment_id',
        'external_shipment_id',
        'carrier_id',
        'manifest_id',
        'manifest_url',
        'service_code',
        'label_id',
        'label_url',
        'invoice_url',
        'tracking_id',
        'tracking_url',
        'shipment_cost_currency',
        'shipment_cost',
        'weight',
        'height',
        'breadth',
        'length',
        'status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function deliveryBoy(): BelongsTo
    {
        return $this->belongsTo(DeliveryBoy::class);
    }
}
