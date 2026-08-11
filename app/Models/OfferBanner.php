<?php

namespace App\Models;

use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class OfferBanner extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $appends = ['banner_images'];

    protected $fillable = [
        'title',
        'slug',
        'scope_type',
        'scope_id',
        'position',
        'visibility_status',
        'display_order',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = generateUniqueSlug(self::class, $value);
    }

    public function getBannerImagesAttribute(): array
    {
        return $this->getMedia(SpatieMediaCollectionName::BANNER_IMAGE())->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
            ];
        })->toArray();
    }

    public function scopeCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'scope_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(SpatieMediaCollectionName::BANNER_IMAGE())->useFallbackUrl('')->singleFile(false);
    }

    protected static function booted(): void
    {
        static::deleting(function ($banner) {
            $banner->clearMediaCollection(SpatieMediaCollectionName::BANNER_IMAGE());
        });
    }
}
