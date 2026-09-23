<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTimeSlot extends Model
{
    protected $fillable = [
        'store_id',
        'day_of_week',
        'start_time',
        'end_time',
        'max_orders',
        'is_active',
    ];

    public function setStartTimeAttribute($value): void
    {
        $this->attributes['start_time'] = $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function getStartTimeAttribute($value): ?string
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }

    public function setEndTimeAttribute($value): void
    {
        $this->attributes['end_time'] = $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function getEndTimeAttribute($value): ?string
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'delivery_time_slot_id');
    }
}

