<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class GlobalProductAttribute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'seller_id',
        'title',
        'label',
        'slug',
        'swatche_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = generateUniqueSlug(self::class, $value);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(GlobalProductAttributeValue::class, 'global_attribute_id');
    }

    public function productVariantAttribute(): HasMany
    {
        return $this->hasMany(ProductVariantAttribute::class, 'global_attribute_id');
    }
}
