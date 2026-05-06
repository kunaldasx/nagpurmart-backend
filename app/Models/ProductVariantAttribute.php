<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariantAttribute extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'global_attribute_id',
        'global_attribute_value_id',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(GlobalProductAttribute::class, 'global_attribute_id');
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(GlobalProductAttributeValue::class, 'global_attribute_value_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
