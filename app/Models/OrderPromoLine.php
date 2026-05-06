<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPromoLine extends Model
{
    use SoftDeletes;

    protected $table = 'order_promo_line';

    protected $fillable = [
        'order_id',
        'promo_id',
        'promo_code',
        'discount_amount',
        'cashback_flag',
        'is_awarded',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'cashback_flag' => 'boolean',
        'is_awarded' => 'boolean',
        'order_id' => 'integer',
        'promo_id' => 'integer',
    ];

    /**
     * Get the order that owns the promo line.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the promo that owns the promo line.
     */
    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }
}
