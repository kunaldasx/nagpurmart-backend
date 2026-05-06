<?php

namespace App\Models;

use App\Enums\Attribute\AttributeTypesEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GlobalProductAttributeValue extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'global_attribute_id',
        'title',
        'swatche_value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(GlobalProductAttribute::class, 'global_attribute_id');
    }

    public function productVariantAttributeValue(): HasMany
    {
        return $this->hasMany(ProductVariantAttribute::class, 'global_attribute_value_id');
    }

    public function getSwatcheValueAttribute()
    {
        return ($this->attribute->swatche_type === AttributeTypesEnum::IMAGE())
            ? $this->getFirstMediaUrl('swatche_image')
            : $this->attributes['swatche_value'] ?? null;
    }

    public
    function registerMediaCollections(): void
    {
        $this->addMediaCollection('swatche_image')->singleFile();
    }

    protected
    static function booted(): void
    {
        static::deleting(function ($attributeValue) {
            $attributeValue->clearMediaCollection('swatche_image');
        });
    }
}
