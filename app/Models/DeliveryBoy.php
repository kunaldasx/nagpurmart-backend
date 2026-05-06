<?php

namespace App\Models;

use App\Enums\DeliveryBoy\DeliveryBoyVerificationStatusEnum;
use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DeliveryBoy extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'delivery_zone_id',
        'status',
        'full_name',
        'address',
        'driver_license',
        'driver_license_number',
        'vehicle_type',
        'vehicle_registration',
        'verification_status',
        'verification_remark',
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
            'verification_status' => DeliveryBoyVerificationStatusEnum::class,
        ];
    }

    public function getDriverLicenseAttribute(): ?array
    {
        return $this->getMedia(SpatieMediaCollectionName::DRIVER_LICENSE())
            ->map(function ($media) {
                return $media->getUrl();
            })->toArray();
    }
    public function getVehicleRegistrationAttribute(): ?array
    {
        return $this->getMedia(SpatieMediaCollectionName::VEHICLE_REGISTRATION())
            ->map(function ($media) {
                return $media->getUrl();
            })->toArray();
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): HasOne
    {
        return $this->hasOne(DeliveryBoyLocation::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function assignments():HasMany
    {
        return $this->hasMany(DeliveryBoyAssignment::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(SpatieMediaCollectionName::DRIVER_LICENSE());
        $this->addMediaCollection(SpatieMediaCollectionName::VEHICLE_REGISTRATION());
        $this->addMediaCollection(SpatieMediaCollectionName::PROFILE_IMAGE())->singleFile();
    }

    protected static function booted(): void
    {
        static::forceDeleting(function ($deliveryBoy) {
            $deliveryBoy->clearMediaCollection(SpatieMediaCollectionName::DRIVER_LICENSE());
            $deliveryBoy->clearMediaCollection(SpatieMediaCollectionName::VEHICLE_REGISTRATION());
            $deliveryBoy->clearMediaCollection(SpatieMediaCollectionName::PROFILE_IMAGE());
        });
    }
}
