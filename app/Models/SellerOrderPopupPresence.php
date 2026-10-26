<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerOrderPopupPresence extends Model
{
    protected $table = 'seller_order_popup_presence';

    protected $fillable = [
        'seller_id',
        'user_id',
        'latitude',
        'longitude',
        'active',
        'last_seen_at',
        'last_offer_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'active' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_offer_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
