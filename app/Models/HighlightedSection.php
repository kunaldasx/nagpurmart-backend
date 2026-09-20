<?php

namespace App\Models;

use App\Enums\HighlightedSection\HighlightedSectionTemplateEnum;
use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class HighlightedSection extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'title', 'slug', 'subtitle', 'template', 'scope_type', 'scope_id',
        'background_color', 'font_color', 'button_color', 'button_text_color', 'sort_order', 'status', 'is_bgimage',
        'badge_color', 'badge_text_color', 'deal_badge_color', 'deal_price_color',
    ];

    protected $casts = [
        'template' => HighlightedSectionTemplateEnum::class,
        'scope_id' => 'integer',
        'sort_order' => 'integer',
        'is_bgimage' => 'boolean',
    ];

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = generateUniqueSlug(self::class, $value);
    }

    public function items(): HasMany
    {
        return $this->hasMany(HighlightedSectionItem::class)->orderBy('sort_order');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(SpatieMediaCollectionName::HIGHLIGHTED_SECTION_BACKGROUND_IMAGES());
    }

    public function getBackgroundImagesAttribute(): array
    {
        return $this->getMedia(SpatieMediaCollectionName::HIGHLIGHTED_SECTION_BACKGROUND_IMAGES())
            ->map(fn ($media) => $media->getFullUrl())
            ->toArray();
    }

    public function scopeCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'scope_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}