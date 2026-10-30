<?php

namespace App\Models;

use App\Enums\Order\OrderItemStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Events\Order\OrderStatusUpdated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @method static whereBetween(string $string, array $array)
 * @method static where(string $string, $DELIVERED)
 */
class OrderItem extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'store_id',
        'title',
        'variant_title',
        'gift_card_discount',
        'admin_commission_amount',
        'seller_commission_amount',
        'commission_settled',
        'return_eligible',
        'return_deadline',
        'returnable_days',
        'discounted_price',
        'promo_discount',
        'discount',
        'is_gift',
        'tax_amount',
        'tax_percent',
        'tax_breakdown',
        'is_tax_inclusive',
        'supplier_tax_name',
        'supplier_tax_number',
        'hsn_code',
        'sku',
        'quantity',
        'stock_deducted_at',
        'stock_restored_at',
        'price',
        'subtotal',
        'status',
        'otp',
        'otp_verified',
    ];

    protected $casts = [
        'return_eligible' => 'boolean',
        'is_gift' => 'boolean',
        'is_tax_inclusive' => 'boolean',
        'tax_breakdown' => 'array',
        'return_deadline' => 'date',
        'stock_deducted_at' => 'datetime',
        'stock_restored_at' => 'datetime',
    ];

    public function getTaxTotalAttribute(): float
    {
        if (is_array($this->tax_breakdown) && $this->tax_breakdown !== []) {
            return round(array_sum(array_column($this->tax_breakdown, 'amount')), 2);
        }

        return round((float) $this->tax_amount * (float) $this->quantity, 2);
    }

    /**
     * Register media collections for order item.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(SpatieMediaCollectionName::ORDER_ITEM_ATTACHMENTS());
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($orderItem) {
            if ($orderItem->isDirty('status')) {
                $oldStatus = $orderItem->getOriginal('status');
                $newStatus = $orderItem->status;

                // If old status is rejected, prevent any status change
                if ($oldStatus === OrderItemStatusEnum::REJECTED()) {
                    throw new \Exception('Order item status cannot be changed after rejection.');
                }

                // Check if the status transition is valid
                if ($oldStatus && !$orderItem->isValidStatusTransition($oldStatus, $newStatus)) {
                    throw new \Exception('Invalid status transition from ' . $oldStatus . ' to ' . $newStatus);
                }
            }
        });
    }

    /**
     * Get the status hierarchy for order items.
     * Higher key means higher status in the progression.
     *
     * @return array
     */
    public static function getStatusHierarchy(): array
    {
        return [
            OrderItemStatusEnum::PENDING() => 1,
            OrderItemStatusEnum::AWAITING_STORE_RESPONSE() => 2,
            OrderItemStatusEnum::ACCEPTED() => 3,
            OrderItemStatusEnum::PREPARING() => 4,
            OrderItemStatusEnum::COLLECTED() => 5,
            OrderItemStatusEnum::DELIVERED() => 6,
            OrderItemStatusEnum::RETURNED() => 7,
            // These statuses are terminal and don't follow the hierarchy
            OrderItemStatusEnum::REFUNDED() => -1,
            OrderItemStatusEnum::REJECTED() => -1,
            OrderItemStatusEnum::CANCELLED() => -1,
            OrderItemStatusEnum::FAILED() => -1,
        ];
    }

    /**
     * Check if a status transition is valid.
     *
     * @param string $oldStatus
     * @param string $newStatus
     * @return bool
     */
    public function isValidStatusTransition(string $oldStatus, string $newStatus): bool
    {
        $hierarchy = self::getStatusHierarchy();

        // If old status is terminal, no transitions are allowed
        if ($hierarchy[$oldStatus] === -1) {
            return false;
        }
        if ($newStatus === OrderItemStatusEnum::REJECTED() && $hierarchy[$oldStatus] >= 3) {
            return false;
        }
        // Prevent changing from ACCEPTED to REJECTED
        if ($oldStatus === OrderItemStatusEnum::ACCEPTED() && $newStatus === OrderItemStatusEnum::REJECTED()) {
            return false;
        }

        // Restrict special transitions
        if ($newStatus === OrderItemStatusEnum::RETURNED() && $oldStatus !== OrderItemStatusEnum::DELIVERED()) {
            return false;
        }
        // Allow transition to terminal statuses from any non-terminal status (except special cases above)
        if ($hierarchy[$newStatus] === -1) {
            return true;
        }

        // For normal progression, only allow moving forward in the hierarchy
        return $hierarchy[$newStatus] > $hierarchy[$oldStatus];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productReviews(): HasOne
    {
        return $this->hasOne(Review::class, 'order_item_id');
    }
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function sellerFeedback(): HasOne
    {
        return $this->hasOne(SellerFeedback::class, 'order_item_id');

    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderItemReturn::class, 'order_item_id');
    }

    public function latestReturn(): HasOne
    {
        return $this->hasOne(OrderItemReturn::class)->latestOfMany();
    }

    public function sellerOrderItem(): HasOne
    {
        return $this->hasOne(SellerOrderItem::class);
    }

    public static function capturePayment(int $orderId): bool
    {
        try {
            $orderItem = self::where('order_id', $orderId)->get();
            if ($orderItem->isNotEmpty()) {
                foreach ($orderItem as $item) {
                    $item->update([
                        'status' => OrderItemStatusEnum::AWAITING_STORE_RESPONSE()
                    ]);
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function paymentFailed(int $orderId): bool
    {
        try {
            return DB::transaction(function () use ($orderId): bool {
                $orderItems = self::where('order_id', $orderId)->lockForUpdate()->get();
                foreach ($orderItems as $item) {
                    $oldStatus = $item->status;
                    $item->update([
                        'status' => OrderItemStatusEnum::FAILED()
                    ]);
                    event(new OrderStatusUpdated(
                        orderItem: $item,
                        oldStatus: $oldStatus,
                        newStatus: OrderItemStatusEnum::FAILED()
                    ));
                }

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to mark order items as payment failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public static function scopeSellerFeedback($orderId, $sellerId)
    {
        return SellerFeedback::where('order_id', $orderId)->where('seller_id', $sellerId)->get()->first();
    }
}
