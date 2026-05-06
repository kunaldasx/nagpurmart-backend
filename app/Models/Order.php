<?php

namespace App\Models;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

/**
 * @method static create(array $array)
 */
class Order extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'slug',
        'email',
        'ip_address',
        'currency_code',
        'currency_rate',
        'payment_method',
        'payment_status',
        'fulfillment_type',
        'is_rush_order',
        'estimated_delivery_time',
        'delivery_time_slot_id',
        'delivery_boy_id',
        'delivery_zone_id',
        'wallet_balance',
        'promo_code',
        'promo_discount',
        'gift_card',
        'gift_card_discount',
        'delivery_charge',
        'handling_charges',
        'per_store_drop_off_fee',
        'subtotal',
        'total_payable',
        'final_total',
        'status',
        'billing_name',
        'billing_address_1',
        'billing_address_2',
        'billing_landmark',
        'billing_zip',
        'billing_phone',
        'billing_address_type',
        'billing_latitude',
        'billing_longitude',
        'billing_city',
        'billing_state',
        'billing_country',
        'billing_country_code',
        'shipping_name',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_landmark',
        'shipping_zip',
        'shipping_phone',
        'shipping_address_type',
        'shipping_latitude',
        'shipping_longitude',
        'shipping_city',
        'shipping_state',
        'shipping_country',
        'shipping_country_code',
        'order_note'
    ];

    protected $casts = [
//        'status' => OrderStatusEnum::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sellerFeedbacks(): HasMany
    {
        return $this->hasMany(SellerFeedback::class, 'order_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function deliveryTimeSlot(): BelongsTo
    {
        return $this->belongsTo(DeliveryTimeSlot::class);
    }

    public function deliveryBoy(): BelongsTo
    {
        return $this->belongsTo(DeliveryBoy::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(SellerOrder::class);
    }

    public function deliveryBoyAssignments(): HasMany
    {
        return $this->hasMany(DeliveryBoyAssignment::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(OrderPaymentTransaction::class);
    }

    public function promoLine(): HasOne
    {
        return $this->hasOne(OrderPromoLine::class);
    }

    /**
     * Scope a query to include delivery boy earnings calculation.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeWithDeliveryBoyEarnings($query)
    {
        return $query->with(['deliveryZone', 'items']);
    }

    /**
     * Calculate delivery boy earnings for this order.
     *
     * @return array|null
     */
    public function getDeliveryBoyEarningsAttribute()
    {
        // Only calculate if delivery zone and items are loaded
        if (!$this->relationLoaded('deliveryZone') || !$this->relationLoaded('items')) {
            return null;
        }

        $deliveryZone = $this->deliveryZone;
        $storeCount = $this->items->pluck('store_id')->unique()->count();

        // Calculate total distance if delivery route is available
        $totalDistance = 0;
        if (!empty($this->delivery_route['total_distance'])) {
            $totalDistance = $this->delivery_route['total_distance'];
        }

        // Calculate earnings components
        $baseFee = $deliveryZone->delivery_boy_base_fee ?? 0;
        $perStorePickupFee = ($deliveryZone->delivery_boy_per_store_pickup_fee ?? 0) * $storeCount;
        $distanceBasedFee = ($deliveryZone->delivery_boy_distance_based_fee ?? 0) * $totalDistance;
        $perOrderIncentive = $deliveryZone->delivery_boy_per_order_incentive ?? 0;

        // Calculate total earnings
        $totalEarnings = $baseFee + $perStorePickupFee + $distanceBasedFee + $perOrderIncentive;

        return [
            'total' => round($totalEarnings, 2),
            'breakdown' => [
                'base_fee' => round($baseFee, 2),
                'per_store_pickup_fee' => round($perStorePickupFee, 2),
                'distance_based_fee' => round($distanceBasedFee, 2),
                'per_order_incentive' => round($perOrderIncentive, 2)
            ]
        ];
    }

    public static function capturePayment(int $orderId): bool
    {
        try {
            $order = self::find($orderId);
            if ($order) {
                $order->payment_status = PaymentStatusEnum::COMPLETED();
                $order->status = OrderStatusEnum::AWAITING_STORE_RESPONSE();
                return $order->save();
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function capturePaymentFromOrder(Order $order): bool
    {
        try {
            if (!$order) {
                return false;
            }

            $order->payment_status = PaymentStatusEnum::COMPLETED();
            $order->status = OrderStatusEnum::AWAITING_STORE_RESPONSE();

            return $order->save();
        } catch (\Exception $e) {
            Log::error('Order payment capture failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'exception' => $e
            ]);
            return false;
        }
    }



    public static function paymentFailed(int $orderId): bool
    {
        try {
            $order = self::find($orderId);
            if ($order) {
                if ($order->wallet_balance > 0) {
                    $data = [
                        'amount' => $order->wallet_balance,
                        'payment_method' => "Refund",
                        'description' => "Order #{$orderId} has failed. The wallet amount of {$order->wallet_balance} used has been refunded."
                    ];
                    WalletService::deductBalance($order->user_id, $data);
                }
                $order->payment_status = PaymentStatusEnum::FAILED();
                $order->status = OrderStatusEnum::FAILED();
                return $order->save();
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
