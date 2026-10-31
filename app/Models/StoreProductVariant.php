<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function expiryBatches(): HasMany
    {
        return $this->hasMany(StoreProductExpiryBatch::class);
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
        $rates = $this->getConfiguredTaxRates();

        return self::calculatePriceTaxDetails($price, $rates, $isInclusive);
    }

    public function getTaxDetailsForLine(float $unitPrice, float $quantity): array
    {
        $isInclusive = (bool) ($this->productVariant?->product?->is_inclusive_tax ?? false);
        return self::calculateLineTaxDetails($unitPrice, $quantity, $this->getConfiguredTaxRates(), $isInclusive);
    }

    public static function calculateLineTaxDetails(float $unitPrice, float $quantity, array $rates, bool $isInclusive): array
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $totalRate = array_sum(array_column($rates, 'rate'));
        $grossAmount = round($unitPrice * $quantity, 2);
        $taxableAmount = round(
            $isInclusive && $totalRate > 0
                ? $grossAmount / (1 + $totalRate / 100)
                : $grossAmount,
            2
        );
        $taxAmount = round(
            $isInclusive
                ? $grossAmount - $taxableAmount
                : $taxableAmount * $totalRate / 100,
            2
        );
        $lineTotal = round($isInclusive ? $grossAmount : $grossAmount + $taxAmount, 2);
        $taxBreakdown = [];
        $componentAmounts = self::allocateTaxComponents($taxableAmount, $taxAmount, $rates);

        foreach (array_values($rates) as $index => $rate) {
            $amount = $componentAmounts[$index];
            $taxBreakdown[] = [
                'tax_group' => $rate['tax_group'] ?? null,
                'name' => $rate['name'] ?? '',
                'rate' => (float) $rate['rate'],
                'unit_amount' => round($amount / $quantity, 2),
                'amount' => $amount,
            ];
        }

        return [
            'is_inclusive' => $isInclusive,
            'tax_percent' => round($totalRate, 2),
            'taxable_amount' => $taxableAmount,
            'unit_taxable_amount' => round($taxableAmount / $quantity, 2),
            'tax_amount' => $taxAmount,
            'unit_tax_amount' => round($taxAmount / $quantity, 2),
            'price_with_tax' => $lineTotal,
            'unit_price_with_tax' => round($lineTotal / $quantity, 2),
            'tax_breakdown' => $taxBreakdown,
        ];
    }

    private function getConfiguredTaxRates(): array
    {
        return $this->productVariant?->product?->taxClasses
            ->flatMap(fn ($taxClass) => $taxClass->taxRates->map(fn ($taxRate) => [
                'tax_group' => $taxClass->title,
                'name' => $taxRate->title,
                'rate' => (float) $taxRate->rate,
            ]))
            ->values()
            ->all() ?? [];
    }

    public static function calculatePriceTaxDetails(float $price, array $rates, bool $isInclusive): array
    {
        $totalRate = array_sum(array_column($rates, 'rate'));
        $grossAmount = round($price, 2);
        $taxableAmount = round(
            $isInclusive && $totalRate > 0
                ? $grossAmount / (1 + $totalRate / 100)
                : $grossAmount,
            2
        );
        $taxAmount = round(
            $isInclusive
                ? $grossAmount - $taxableAmount
                : $taxableAmount * $totalRate / 100,
            2
        );
        $taxBreakdown = [];
        $componentAmounts = self::allocateTaxComponents($taxableAmount, $taxAmount, $rates);

        foreach (array_values($rates) as $index => $rate) {
            $amount = $componentAmounts[$index];
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
            'price_with_tax' => round($isInclusive ? $grossAmount : $grossAmount + $taxAmount, 2),
            'tax_breakdown' => $taxBreakdown,
        ];
    }

    private static function allocateTaxComponents(float $taxableAmount, float $totalTax, array $rates): array
    {
        $components = [];
        $allocatedCents = 0;

        foreach (array_values($rates) as $index => $rate) {
            $rawCents = $taxableAmount * (float) $rate['rate'];
            $baseCents = (int) floor($rawCents + 1e-8);
            $components[] = [
                'index' => $index,
                'cents' => $baseCents,
                'remainder' => $rawCents - $baseCents,
            ];
            $allocatedCents += $baseCents;
        }

        $remainingCents = (int) round($totalTax * 100) - $allocatedCents;
        if ($remainingCents > 0) {
            usort($components, fn ($left, $right) => $right['remainder'] <=> $left['remainder']);
            for ($index = 0; $remainingCents > 0; $index++, $remainingCents--) {
                $components[$index % count($components)]['cents']++;
            }
        } elseif ($remainingCents < 0) {
            usort($components, fn ($left, $right) => $left['remainder'] <=> $right['remainder']);
            for ($index = 0; $remainingCents < 0; $index++) {
                $componentIndex = $index % count($components);
                if ($components[$componentIndex]['cents'] > 0) {
                    $components[$componentIndex]['cents']--;
                    $remainingCents++;
                }
            }
        }

        usort($components, fn ($left, $right) => $left['index'] <=> $right['index']);

        return array_map(fn ($component) => $component['cents'] / 100, $components);
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
        return $this->getTaxDetailsForPrice((float) $this->attributes['price'])['taxable_amount'];
    }
    public function getSpecialPriceExcludeTaxAttribute(): float
    {
        $price = (float) ($this->is_special_price_active
            ? $this->attributes['special_price']
            : ($this->attributes['original_special_price'] ?? $this->attributes['price']));

        return $this->getTaxDetailsForPrice($price)['taxable_amount'];
    }

    public function getWholesalePriceExcludeTaxAttribute(): ?float
    {
        return isset($this->attributes['wholesale_price'])
            ? $this->getTaxDetailsForPrice((float) $this->attributes['wholesale_price'])['taxable_amount']
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
            ? $this->getTaxDetailsForPrice((float) $this->attributes['original_special_price'])['taxable_amount']
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
