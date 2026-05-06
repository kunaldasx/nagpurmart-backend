<?php

namespace App\Models;

use App\Enums\Order\OrderItemReturnPickupStatusEnum;
use App\Enums\Order\OrderItemReturnStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class OrderItemReturn extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'order_item_returns';

    protected $appends = ['images'];

    protected $fillable = [
        'order_item_id',
        'order_id',
        'user_id',
        'seller_id',
        'store_id',
        'delivery_boy_id',
        'reason',
        'refund_amount',
        'seller_comment',
        'pickup_status',
        'return_status',
        'seller_approved_at',
        'picked_up_at',
        'received_at',
        'refund_processed_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'seller_approved_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'received_at' => 'datetime',
        'refund_processed_at' => 'datetime',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function getImagesAttribute()
    {
        return $this->getMedia(SpatieMediaCollectionName::ITEM_RETURN_IMAGES())
            ->map(fn($media) => $media->getUrl());
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function deliveryBoy(): BelongsTo
    {
        return $this->belongsTo(DeliveryBoy::class);
    }

    protected
    static function booted(): void
    {
        static::deleted(function ($orderReturn) {
            $orderReturn->clearMediaCollection();
        });
    }
}
