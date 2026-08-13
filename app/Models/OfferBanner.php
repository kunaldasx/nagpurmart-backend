<?php

namespace App\Models;

use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class OfferBanner extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title', 'slug', 'position', 'scope_type', 'scope_id', 'visibility_status', 'display_order', 'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug) && !empty($model->title)) {
                $model->slug = generateUniqueSlug(self::class, $model->title);
            }
        });

        static::deleting(function ($model) {
            $model->clearMediaCollection('offer_banner_images');
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfferBannerItem::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('offer_banner_images');
    }

    public function getImagesAttribute(): array
    {
        return $this->getMedia('offer_banner_images')->map(fn($m) => $m->getFullUrl())->toArray();
    }
}
