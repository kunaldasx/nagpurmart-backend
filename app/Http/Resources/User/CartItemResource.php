<?php

namespace App\Http\Resources\User;

use App\Models\Review;
use App\Services\DeliveryZoneService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variantPricing = $this->variant->storeProductVariants->where('store_id', $this->store_id)->first();
        $storeVariant = $this->variant->storeProductVariants->where('store_id', $this->store_id)->first();
        $orderMode = $request->input('order_mode', 'regular');
        if (!in_array($orderMode, ['regular', 'wholesale'], true)) {
            $orderMode = 'regular';
        }
        $rawPrice = $storeVariant?->getPriceForMode($orderMode, false);
        $taxDetails = $this->is_gift || $rawPrice === null
            ? [
                'is_inclusive' => false,
                'tax_percent' => 0,
                'taxable_amount' => $this->is_gift ? 1 : 0,
                'unit_taxable_amount' => $this->is_gift ? 1 : 0,
                'tax_amount' => 0,
                'unit_tax_amount' => 0,
                'price_with_tax' => $this->is_gift ? 1 : 0,
                'unit_price_with_tax' => $this->is_gift ? 1 : 0,
                'tax_breakdown' => [],
            ]
            : $storeVariant->getTaxDetailsForLine($rawPrice, (float) $this->quantity);
        $reviews = Review::scopeProductRatingStats($this->product->id);
        if (isset($request->latitude) && isset($request->longitude)) {
            $this->product->user_latitude = $request->latitude;
            $this->product->user_longitude = $request->longitude;
            $this->product->zone_info = DeliveryZoneService::getZonesAtPoint($request->latitude, $request->longitude);
        }
        return [
            'id' => $this->id,
            'cart_id' => $this->cart_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'store_id' => $this->store_id,
            'quantity' => $this->quantity,
            'save_for_later' => $this->save_for_later,
            'is_gift' => (bool)$this->is_gift,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->title,
                'slug' => $this->product->slug,
                'minimum_order_quantity' => $this->product->minimum_order_quantity,
                'quantity_step_size' => $this->product->quantity_step_size,
                'total_allowed_quantity' => $this->product->total_allowed_quantity,
                'is_attachment_required' => $this->product->is_attachment_required == "1" ? true : false,
                'image' => $this->product->main_image ?? null,
                'estimated_delivery_time' => $this->product->estimated_delivery_time,
                'image_fit' => $this->product->image_fit,
                'store_status' => $this->product->variants->first()->storeProductVariants->first()->store->checkStoreStatus() ?? [],
                'ratings' => $reviews['average_rating'] ?? 0,
                'rating_count' => $reviews['total_reviews'] ?? 0,
            ],
            'variant' => [
                'id' => $this->variant->id,
                'title' => $this->variant->title,
                'slug' => $this->variant->slug,
                'image' => $this->variant->image ?? null,
                'price' => $variantPricing?->price ?? 0,
                'special_price' => $variantPricing?->special_price ?? 0,
                'wholesale_price' => $variantPricing?->wholesale_price ?? 0,
                'taxable_price' => $taxDetails['unit_taxable_amount'],
                'price_with_tax' => $taxDetails['unit_price_with_tax'],
                'tax_percent' => $taxDetails['tax_percent'],
                'tax_amount' => $taxDetails['unit_tax_amount'],
                'tax_total' => $taxDetails['tax_amount'],
                'taxable_subtotal' => $taxDetails['taxable_amount'],
                'subtotal_with_tax' => $taxDetails['price_with_tax'],
                'tax_breakdown' => $taxDetails['tax_breakdown'],
                'is_tax_inclusive' => $taxDetails['is_inclusive'],
                'stock' => $storeVariant?->stock ?? 0,
                'sku' => $storeVariant?->sku ?? null,
            ],
            'store' => [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
                'total_products' => $this->store->product_count ?? 0,
                'status' => optional(
                        $this->store
                    )->checkStoreStatus() ?? [],
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
