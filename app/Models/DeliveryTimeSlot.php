<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTimeSlot extends Model
{
    protected $fillable = [
        'store_id',
        'start_time',
        'end_time',
        'max_orders',
        'is_active',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

