<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class StoreProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_variant_id',
        'store_id',
        'sku',
        'price',
        'special_price',
        'cost',
        'stock'
    ];
    protected $appends = ['price_exclude_tax','special_price_exclude_tax'];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    private function getTaxAdjustedPrice($price): ?float
    {
        if ($price === null) {
            return null;
        }

        if ($this->productVariant?->product?->is_inclusive_tax == 0 && $this->productVariant?->product?->taxClasses) {
            $totalTaxRate = $this->productVariant->product->taxClasses->sum(function ($taxClass) {
                return $taxClass->taxRates->sum('rate');
            });
            return $price + ($price * $totalTaxRate / 100);
        }

        return $price;
    }

    public function getCategoryCommissionAttribute()
    {
        return DB::table('store_product_variants')->select(['categories.id', 'categories.commission'])
            ->join('product_variants', 'store_product_variants.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('store_product_variants.id', $this->id)->first();
    }

    public function getPriceAttribute($value): ?int
    {
        return $this->getTaxAdjustedPrice($value);
    }

    public function getSpecialPriceAttribute($value): ?int
    {
        return $this->getTaxAdjustedPrice($value);
    }

    public function getPriceExcludeTaxAttribute(): int
    {
        return $this->attributes['price'];
    }
    public function getSpecialPriceExcludeTaxAttribute(): int
    {
        return $this->attributes['special_price'];
    }

    public static function scopeTaxPercentage($basePrice, $priceWithTax): int
    {
        if ($basePrice == 0) {
            return 0; // Avoid division by zero
        }

        $taxPercentage = (($priceWithTax - $basePrice) / $basePrice) * 100;

        return round($taxPercentage, 2); // Round to 2 decimal places
    }
}

