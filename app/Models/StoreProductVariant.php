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
        'wholesale_price',
        'original_special_price',
        'special_price_ends_at',
        'unit_price_basis_quantity',
        'unit_price_basis_unit',
        'cost',
        'stock'
    ];
    protected $casts = [
        'original_special_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'unit_price_basis_quantity' => 'decimal:3',
        'special_price_ends_at' => 'datetime',
    ];
    protected $appends = ['price_exclude_tax','special_price_exclude_tax','wholesale_price_exclude_tax'];

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

        return $this->getTaxDetailsForPrice((float) $price)['price_with_tax'];
    }

    public function getTaxDetailsForPrice(float $price): array
    {
        $product = $this->productVariant?->product;
        $isInclusive = (bool) ($product?->is_inclusive_tax ?? false);
        $rates = $product?->taxClasses
            ->flatMap(fn ($taxClass) => $taxClass->taxRates->map(fn ($taxRate) => [
                'tax_group' => $taxClass->title,
                'name' => $taxRate->title,
                'rate' => (float) $taxRate->rate,
            ]))
            ->values()
            ->all() ?? [];

        return self::calculatePriceTaxDetails($price, $rates, $isInclusive);
    }

    public static function calculatePriceTaxDetails(float $price, array $rates, bool $isInclusive): array
    {
        $totalRate = array_sum(array_column($rates, 'rate'));
        $taxableAmount = $isInclusive && $totalRate > 0
            ? $price / (1 + $totalRate / 100)
            : $price;
        $taxAmount = $isInclusive
            ? $price - $taxableAmount
            : $taxableAmount * $totalRate / 100;
        $taxableAmount = round($taxableAmount, 2);
        $taxAmount = round($taxAmount, 2);
        $remainingTax = $taxAmount;
        $taxBreakdown = [];

        foreach (array_values($rates) as $index => $rate) {
            $amount = $index === count($rates) - 1
                ? $remainingTax
                : round($taxableAmount * (float) $rate['rate'] / 100, 2);
            $remainingTax = round($remainingTax - $amount, 2);
            $taxBreakdown[] = [
                'tax_group' => $rate['tax_group'] ?? null,
                'name' => $rate['name'] ?? '',
                'rate' => (float) $rate['rate'],
                'amount' => $amount,
            ];
        }

        return [
            'is_inclusive' => $isInclusive,
            'tax_percent' => round($totalRate, 2),
            'taxable_amount' => $taxableAmount,
            'tax_amount' => $taxAmount,
            'price_with_tax' => round($isInclusive ? $price : $price + $taxAmount, 2),
            'tax_breakdown' => $taxBreakdown,
        ];
    }

    public function getCategoryCommissionAttribute()
    {
        return DB::table('store_product_variants')->select(['categories.id', 'categories.commission'])
            ->join('product_variants', 'store_product_variants.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('store_product_variants.id', $this->id)->first();
    }

    public function getPriceAttribute($value): ?float
    {
        return $this->getTaxAdjustedPrice($value);
    }

    public function getSpecialPriceAttribute($value): ?float
    {
        $price = $this->is_special_price_active
            ? $value
            : ($this->attributes['original_special_price'] ?? $this->attributes['price'] ?? null);
        return $this->getTaxAdjustedPrice($price);
    }

    public function getIsSpecialPriceActiveAttribute(): bool
    {
        $specialPrice = (float)($this->attributes['special_price'] ?? 0);
        return $specialPrice > 0
            && $specialPrice < (float)($this->attributes['price'] ?? 0)
            && (!isset($this->attributes['special_price_ends_at'])
                || now()->lt($this->special_price_ends_at));
    }

    public function getPriceExcludeTaxAttribute(): float
    {
        return (float) $this->attributes['price'];
    }
    public function getSpecialPriceExcludeTaxAttribute(): float
    {
        return (float) ($this->is_special_price_active
            ? $this->attributes['special_price']
            : ($this->attributes['original_special_price'] ?? $this->attributes['price']));
    }

    public function getWholesalePriceExcludeTaxAttribute(): ?float
    {
        return isset($this->attributes['wholesale_price'])
            ? (float)$this->attributes['wholesale_price']
            : null;
    }

    public function getWholesalePriceAttribute($value): ?float
    {
        return $this->getTaxAdjustedPrice($value);
    }

    public function getPriceForMode(string $mode = 'regular', bool $includeTax = true): ?float
    {
        if ($mode === 'wholesale') {
            $price = $this->attributes['wholesale_price'] ?? null;
            return $includeTax ? $this->getTaxAdjustedPrice($price) : ($price === null ? null : (float)$price);
        }

        $price = $this->is_special_price_active
            ? ($this->attributes['special_price'] ?? null)
            : ($this->attributes['price'] ?? null);
        return $includeTax ? $this->getTaxAdjustedPrice($price) : ($price === null ? null : (float)$price);
    }

    public function getOriginalSpecialPriceExcludeTaxAttribute(): ?float
    {
        return isset($this->attributes['original_special_price'])
            ? (float)$this->attributes['original_special_price']
            : null;
    }

    public function getSpecialPriceRawAttribute(): ?float
    {
        return isset($this->attributes['special_price'])
            ? (float)$this->attributes['special_price']
            : null;
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
