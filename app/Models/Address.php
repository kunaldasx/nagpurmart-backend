<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static where(array $array)
 */
class Address extends Model
{
    protected $fillable = [
        'user_id',
        'address_line1',
        'address_line2',
        'city',
        'landmark',
        'state',
        'zipcode',
        'mobile',
        'address_type',
        'country',
        'country_code',
        'latitude',
        'longitude',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'address_line1' => 'string',
        'address_line2' => 'string',
        'city' => 'string',
        'landmark' => 'string',
        'state' => 'string',
        'zipcode' => 'string',
        'mobile' => 'string',
        'address_type' => 'string',
        'country' => 'string',
        'country_code' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
