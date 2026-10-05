<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        // Format attributes as key-value pairs
        $attributes = [];

        // Try to get the variant attributes directly from the variant
        if ($this->relationLoaded('attributes')) {
            foreach ($this->attributes as $attribute) {
                if ($attribute->attribute && $attribute->attributeValue) {
                    $attributeSlug = $attribute->attribute->slug;
                    $attributeValue = $attribute->attributeValue->title;
                    $attributes[$attributeSlug] = $attributeValue;
                }
            }
        } else {
            // If attributes aren't loaded, load them now
            $this->load(['attributes.attribute', 'attributes.attributeValue']);

            foreach ($this->attributes as $attribute) {
                if ($attribute->attribute && $attribute->attributeValue) {
                    $attributeSlug = $attribute->attribute->slug;
                    $attributeValue = $attribute->attributeValue->title;
                    $attributes[$attributeSlug] = $attributeValue;
                }
            }
        }

        $cartItem = $this->isInUserCart();
        $storePricing = $this->storeProductVariants->first();
        $unitPrice = null;
        $unitPriceBasis = null;
        $quantity = (float)($this->net_quantity ?? 0);
        $unit = $this->net_quantity_unit;
        $unitDivisor = match ($unit) {
            'g', 'ml' => $quantity / 100,
            'kg', 'l' => $quantity * 10,
            'item' => $quantity,
            default => 0,
        };
        if ($quantity > 0 && $unitDivisor > 0 && $storePricing) {
            $price = $storePricing->getPriceForMode();
            if ($price !== null) {
                $unitPrice = round($price / $unitDivisor, 2);
                $unitPriceBasis = match ($unit) {
                    'g', 'kg' => '100 g',
                    'ml', 'l' => '100 ml',
                    'item' => '1 item',
                };
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => $this->image ?? '',
            'weight' => (float)$this->weight ?? 0,
            'net_quantity' => $this->net_quantity !== null ? (float)$this->net_quantity : null,
            'net_quantity_unit' => $unit,
            'unit_price' => $unitPrice,
            'unit_price_basis' => $unitPriceBasis,
            'height' => (float)$this->height ?? 0,
            'breadth' => (float)$this->breadth ?? 0,
            'length' => (float)$this->length ?? 0,
            'availability' => $this->availability,
            'cart_item' => $cartItem,
            'barcode' => $this->barcode,
            'is_default' => $this->is_default,
            'price' => $storePricing?->price,
            'special_price' => $storePricing?->special_price,
            'wholesale_price' => $storePricing?->wholesale_price,
            'original_price' => $storePricing?->price,
            'original_special_price' => $storePricing?->original_special_price_exclude_tax,
            'special_price_ends_at' => $storePricing?->special_price_ends_at?->toISOString(),
            'is_special_price_active' => $storePricing?->is_special_price_active ?? false,
            'store_id' => $storePricing?->store_id,
            'store_slug' => $storePricing?->store?->slug,
            'store_name' => $storePricing?->store?->name,
            'stock' => $storePricing?->stock,
            'sku' => $storePricing?->sku,
            'attributes' => $attributes,
        ];
    }
}
