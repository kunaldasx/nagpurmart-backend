<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'center_latitude',
        'center_longitude',
        'radius_km',
        'boundary_json',
        'rush_delivery_enabled',
        'delivery_time_per_km',
        'rush_delivery_time_per_km',
        'rush_delivery_charges',
        'regular_delivery_charges',
        'free_delivery_amount',
        'distance_based_delivery_charges',
        'per_store_drop_off_fee',
        'handling_charges',
        'buffer_time',
        'status',
        'delivery_boy_base_fee',
        'delivery_boy_per_store_pickup_fee',
        'delivery_boy_distance_based_fee',
        'delivery_boy_per_order_incentive',
    ];

    protected function casts(): array
    {
        return [
            'center_latitude' => 'decimal:8',
            'center_longitude' => 'decimal:8',
            'radius_km' => 'double',
            'boundary_json' => 'array',
            'rush_delivery_enabled' => 'boolean',
            'delivery_time_per_km' => 'integer',
            'distance_based_delivery_charges' => 'integer',
            'rush_delivery_time_per_km' => 'integer',
            'rush_delivery_charges' => 'integer',
            'regular_delivery_charges' => 'integer',
            'free_delivery_amount' => 'integer',
            'per_store_drop_off_fee' => 'integer',
            'handling_charges' => 'integer',
            'buffer_time' => 'integer',
            'delivery_boy_base_fee' => 'decimal:2',
            'delivery_boy_per_store_pickup_fee' => 'decimal:2',
            'delivery_boy_distance_based_fee' => 'decimal:2',
            'delivery_boy_per_order_incentive' => 'decimal:2',
        ];
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = generateUniqueSlug(self::class, $value);
    }

    public function deliveryBoys(): HasMany
    {
        return $this->hasMany(DeliveryBoy::class);
    }
}
