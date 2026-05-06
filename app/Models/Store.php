<?php

namespace App\Models;

use App\Enums\SpatieMediaCollectionName;
use App\Enums\Store\StoreFulfillmentTypeEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Enums\Store\StoreVerificationStatusEnum;
use App\Enums\Store\StoreVisibilityStatusEnum;
use App\Services\DeliveryZoneService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Store extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $appends = ['distance'];
    protected $fillable = [
        'seller_id',
        'name',
        'slug',
        'address',
        'city',
        'landmark',
        'state',
        'zipcode',
        'country',
        'country_code',
        'latitude',
        'longitude',
        'contact_email',
        'contact_number',
        'description',
        'timing',
        'address_proof',
        'voided_check',
        'tax_name',
        'tax_number',
        'bank_name',
        'bank_branch_code',
        'account_holder_name',
        'account_number',
        'routing_number',
        'bank_account_type',
        'currency_code',
        'status',
        'max_delivery_distance',
        'order_preparation_time',
        'promotional_text',
        'about_us',
        'return_replacement_policy',
        'refund_policy',
        'terms_and_conditions',
        'delivery_policy',
        'domestic_shipping_charges',
        'international_shipping_charges',
        'metadata',
        'verification_status',
        'visibility_status',
        'fulfillment_type'
    ];

    protected $casts = [
        'metadata' => 'array',
        'max_delivery_distance' => 'double',
        'domestic_shipping_charges' => 'decimal:2',
        'international_shipping_charges' => 'decimal:2',
        'fulfillment_type' => StoreFulfillmentTypeEnum::class,
//        'status' => StoreStatusEnum::class,
        'verification_status' => StoreVerificationStatusEnum::class,
        'visibility_status' => StoreVisibilityStatusEnum::class,
    ];

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = generateUniqueSlug(self::class, $value);
    }

    public function getDistanceAttribute()
    {
        if (!isset($this->user_latitude) || !isset($this->user_longitude)) {
            return null;
        }

        return DeliveryZoneService::calculateDistance(
            $this->user_latitude,
            $this->user_longitude,
            $this->latitude,
            $this->longitude
        );
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function productVariants(): HasMany
    {
        return $this->hasMany(StoreProductVariant::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(StoreInventoryLog::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(SellerFeedback::class, 'store_id');
    }

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(DeliveryZone::class, 'store_zone', 'store_id', 'zone_id');
    }

    public function getProductCountAttribute(): int
    {
        $storeId = $this->id ?? 0;
        return Product::whereHas('variants.storeProductVariants', function ($q) use ($storeId) {
            $q->where('store_id', $storeId);
        })->count();
    }

    // get store all product avg rating
    public function getAvgProductsRatingAttribute()
    {
        return Review::whereHas('product.variants.storeProductVariants', function ($q) {
            $q->where('store_id', $this->id);
        })
            ->avg('rating');
    }

    public function getAvgStoreRatingAttribute()
    {
        return SellerFeedback::where('store_id', $this->id)->avg('rating');
    }
    public function getTotalStoreFeedbackAttribute()
    {
        return SellerFeedback::where('store_id', $this->id)->count();
    }

    public function getStoreLogoAttribute(): ?string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::STORE_LOGO()) ?? null;
    }

    public function getStoreBannerAttribute(): ?string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::STORE_BANNER()) ?? null;
    }

    public function getAddressProofAttribute(): ?string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::ADDRESS_PROOF()) ?? null;
    }

    public function getVoidedCheckAttribute(): ?string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::VOIDED_CHECK()) ?? null;
    }

    /**
     * Check store status based on status field
     */
    public function checkStoreStatus(): array
    {
        $isOpen = $this->status === StoreStatusEnum::ONLINE();

        return [
            'is_open' => $isOpen,
            'status' => $this->status,
        ];
    }

    /**
     * Check if store is online
     */
    public function isOnline(): bool
    {
        return $this->status === StoreStatusEnum::ONLINE();
    }

    /**
     * Check if store is offline
     */
    public function isOffline(): bool
    {
        return $this->status === StoreStatusEnum::OFFLINE();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(SpatieMediaCollectionName::STORE_LOGO())->singleFile();
        $this->addMediaCollection(SpatieMediaCollectionName::STORE_BANNER())->singleFile();
    }
}
