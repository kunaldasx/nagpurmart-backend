<?php

namespace App\Models;

use App\Enums\HighlightedSection\HighlightedSectionItemTypeEnum;
use App\Enums\SpatieMediaCollectionName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class HighlightedSectionItem extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $appends = ['image'];

    protected $fillable = [
        'highlighted_section_id', 'item_type', 'item_id', 'title', 'subtitle', 'sort_order',
    ];

    protected $casts = ['item_type' => HighlightedSectionItemTypeEnum::class, 'item_id' => 'integer', 'sort_order' => 'integer'];

    public function getImageAttribute(): string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::HIGHLIGHTED_SECTION_ITEM_IMAGE());
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(HighlightedSection::class, 'highlighted_section_id');
    }

    public function resolvedItem(): ?Model
    {
        return match ($this->item_type?->value ?? $this->item_type) {
            'product' => Product::find($this->item_id),
            'category' => Category::find($this->item_id),
            'brand' => Brand::find($this->item_id),
            default => null,
        };
    }
}