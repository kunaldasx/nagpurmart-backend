<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryBoyCashTransaction extends Model
{
    protected $fillable = [
        'delivery_boy_assignment_id',
        'order_id',
        'delivery_boy_id',
        'amount',
        'transaction_type',
        'transaction_date',
    ];

    /**
     * Get the delivery boy assignment associated with the transaction.
     */
    public function deliveryBoyAssignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryBoyAssignment::class);
    }

    /**
     * Get the order associated with the transaction.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the delivery boy associated with the transaction.
     */
    public function deliveryBoy(): BelongsTo
    {
        return $this->belongsTo(DeliveryBoy::class);
    }
}
