<?php

namespace App\Http\Resources\User;

use App\Models\Review;
use App\Services\DeliveryZoneService;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    public function toArray($request): array
    {
        $reviews = Review::scopeProductRatingStats($this->product->id);

        if (isset($request->latitude) && isset($request->longitude)) {
            $this->product->user_latitude = $request->latitude;
            $this->product->user_longitude = $request->longitude;
            $this->product->zone_info = DeliveryZoneService::getZonesAtPoint($request->latitude, $request->longitude);
        }
        return [
            'id' => $this->id,
            'wishlist_id' => $this->wishlist_id,
            'product' => [
                'id' => $this->product->id,
                'title' => $this->product->title,
                'slug' => $this->product->slug,
                'image' => $this->product->main_image,
                'minimum_order_quantity' => $this->product->minimum_order_quantity,
                'quantity_step_size' => $this->product->quantity_step_size,
                'total_allowed_quantity' => $this->product->total_allowed_quantity,
                'short_description' => $this->product->short_description,
                'estimated_delivery_time' => $this->product->estimated_delivery_time,
                'image_fit' => $this->product->image_fit,
                'store_status' => $this->product->variants->first()->storeProductVariants->first()->store->checkStoreStatus() ?? [],
                'ratings' => $reviews['average_rating'] ?? 0,
                'rating_count' => $reviews['total_reviews'] ?? 0,
            ],
            'variant' => $this->when($this->variant, [
                'id' => $this->variant?->id,
                'sku' => $this->variant?->sku,
                'image' => $this->variant?->image,
                'price' => $this->variant?->storeProductVariants->first()->price,
                'special_price' => $this->variant?->storeProductVariants->first()->special_price,
                'store_id' => $this->variant?->storeProductVariants->first()->store_id ?? null,
                'store_slug' => $this->variant?->storeProductVariants->first()->store->slug ?? null,
                'store_name' => $this->variant?->storeProductVariants->first()->store->name ?? null,
                'stock' => $this->variant?->storeProductVariants->first()->stock ?? null,
                'sku' => $this->variant?->storeProductVariants->first()->sku ?? null,
            ]),
            'store' => [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
