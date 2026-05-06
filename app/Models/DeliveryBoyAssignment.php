<?php

namespace App\Models;

use App\Enums\DeliveryBoy\EarningPaymentStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\WalletTransaction;

class DeliveryBoyAssignment extends Model
{
    protected $fillable = [
        'order_id',
        'delivery_boy_id',
        'order_item_id',
        'return_id',
        'assignment_type',
        'assigned_at',
        'status',
        'base_fee',
        'per_store_pickup_fee',
        'distance_based_fee',
        'per_order_incentive',
        'total_earnings',
        'payment_status',
        'paid_at',
        'transaction_id',
        'cod_cash_collected',
        'cod_cash_submitted',
        'cod_submission_status',
    ];
    protected $casts = [
        'cod_cash_collected' => 'decimal:2',
        'cod_cash_submitted' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
    public function return(): BelongsTo
    {
        return $this->belongsTo(OrderItemReturn::class);
    }
    public function deliveryBoy(): BelongsTo
    {
        return $this->belongsTo(DeliveryBoy::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /**
     * Get the payment status attribute with the enum value
     */
    public function getPaymentStatusAttribute($value)
    {
        return $value ?? null;
    }

    /**
     * Set the payment status attribute with the enum value
     */
    public function setPaymentStatusAttribute($value)
    {
        $this->attributes['payment_status'] = $value instanceof EarningPaymentStatusEnum ? $value->value : $value;
    }
}
