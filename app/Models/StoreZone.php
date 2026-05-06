<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreZone extends Model
{
    use HasFactory;

    protected $table = 'store_zone';

    protected $fillable = [
        'store_id',
        'zone_id',
    ];

    /**
     * Get the store associated with this StoreZone.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Get the delivery zone associated with this StoreZone.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }
}
