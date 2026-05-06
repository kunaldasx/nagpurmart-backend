<?php

namespace App\Models;

use App\Enums\Seller\SellerVerificationStatusEnum;
use App\Enums\Seller\SellerVisibilityStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\Subscription\SellerSubscriptionStatusEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @method static where(string $string, $id)
 * @method static create(array $sellerData)
 * @method static find(string $id)
 * @method static count()
 * @method static findOrFail(mixed $id)
 */
class Seller extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    public function statements(): HasMany
    {
        return $this->hasMany(\App\Models\SellerStatement::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'address',
        'city',
        'landmark',
        'state',
        'zipcode',
        'country',
        'country_code',
        'latitude',
        'longitude',
        'business_license',
        'articles_of_incorporation',
        'national_identity_card',
        'authorized_signature',
        'verification_status',
        'metadata',
        'visibility_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'metadata' => 'array',
        ];
    }

    public function getVerificationStatusAttribute($value){
        return !empty($value) ? $value : SellerVerificationStatusEnum::NotApproved();
    }

    public function getVisibilityStatusAttribute($value)
    {
        return !empty($value) ? $value : SellerVisibilityStatusEnum::Draft();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function storeFollowingSellers(): HasMany
    {
        return $this->hasMany(FollowingSeller::class);
    }

    public function countryDetails(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country', 'name');
    }

    public function subscriptionPlan(): HasMany
    {
        return $this->hasMany(SellerSubscription::class);
    }

    /**
     * Get the seller's current active subscription (if any).
     */
    public function activeSubscription(): HasOne
    {
        $now = now();

        return $this->hasOne(SellerSubscription::class)
            ->where('status', SellerSubscriptionStatusEnum::ACTIVE())
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')
                  ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now);
            })
            ->latestOfMany('start_date');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(SpatieMediaCollectionName::BUSINESS_LICENSE())->singleFile();
        $this->addMediaCollection(SpatieMediaCollectionName::ARTICLES_OF_INCORPORATION())->singleFile();
        $this->addMediaCollection(SpatieMediaCollectionName::NATIONAL_IDENTITY_CARD())->singleFile();
        $this->addMediaCollection(SpatieMediaCollectionName::AUTHORIZED_SIGNATURE())->singleFile();
    }

    protected static function booted(): void
    {
        static::deleting(function ($category) {
            $category->clearMediaCollection(SpatieMediaCollectionName::BUSINESS_LICENSE());
            $category->clearMediaCollection(SpatieMediaCollectionName::ARTICLES_OF_INCORPORATION());
            $category->clearMediaCollection(SpatieMediaCollectionName::NATIONAL_IDENTITY_CARD());
            $category->clearMediaCollection(SpatieMediaCollectionName::AUTHORIZED_SIGNATURE());
        });
    }
}
