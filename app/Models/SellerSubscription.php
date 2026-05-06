<?php

namespace App\Models;

use App\Enums\Subscription\SellerSubscriptionStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;

class SellerSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'plan_id',
        'status',
        'start_date',
        'end_date',
        'price_paid'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price_paid' => 'decimal:2',
        'status' => SellerSubscriptionStatusEnum::class,
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(SellerSubscriptionSnapshot::class, 'seller_subscription_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SubscriptionTransaction::class, 'seller_subscription_id');
    }

    public function getStatusAttribute($value): string
    {
        return $value;
    }

    /**
     * Get the latest ACTIVE or PENDING subscription for the given seller.
     */
    public static function currentForSeller(int $sellerId): ?self
    {
        $key = self::cacheKeyForCurrent($sellerId);
        // Keep a short TTL to avoid staleness but reduce frequent DB hits
        return Cache::remember($key, now()->addMinutes(5), function () use ($sellerId) {
            return self::with(['plan.limits', 'snapshot', 'transactions'])
                ->where('seller_id', $sellerId)
                ->where('status', SellerSubscriptionStatusEnum::ACTIVE())
                ->orderByDesc('id')
                ->first();
        });
    }

    public static function cacheKeyForCurrent(int $sellerId): string
    {
        return "seller:{$sellerId}:subscription:current";
    }

    /**
     * Small helper to get a CSS badge class based on current status.
     */
    public function badgeClass(): string
    {
        $statusVal = (string)$this->status;
        if ($statusVal === (string)SellerSubscriptionStatusEnum::ACTIVE()) {
            return 'bg-green-lt';
        }
        if ($statusVal === (string)SellerSubscriptionStatusEnum::PENDING()) {
            return 'bg-yellow-lt';
        }
        return 'bg-secondary';
    }
}
