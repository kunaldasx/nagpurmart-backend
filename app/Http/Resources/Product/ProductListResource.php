<?php

namespace App\Http\Resources\Product;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $reviews = Review::scopeProductRatingStats($this->id);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'seller_id' => $this->seller_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'short_description' => $this->short_description,
            'category' => $this->category?->slug,
            'brand' => $this->brand?->slug,
            'category_name' => $this->category?->title,
            'brand_name' => $this->brand?->title,
            'seller' => $this->seller?->user->name ?? "N/A",
            'indicator' => $this->indicator,
            'favorite' => $this->favorite,
            'estimated_delivery_time' => $this->estimated_delivery_time,
            'base_prep_time' => $this->base_prep_time ?? 0,
            'ratings' => $reviews['average_rating'] ?? 0,
            'rating_count' => $reviews['total_reviews'] ?? 0,
            'main_image' => $this->main_image,
            'image_fit' => $this->image_fit,
            'item_count_in_cart' => $this->itemCountInCart ?? 0,
            'is_save_for_later' => $this->IsSaveForLater ?? false,
            'additional_images' => $this->additional_images,
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'quantity_step_size' => $this->quantity_step_size,
            'total_allowed_quantity' => $this->total_allowed_quantity,
            'is_returnable' => (float)$this->is_returnable,
            'is_attachment_required' => (float)$this->is_attachment_required,
            'requires_otp' => (float)$this->requires_otp,
            'tags' => $this->tags,
            'warranty_period' => $this->warranty_period,
            'guarantee_period' => $this->guarantee_period,
            'made_in' => $this->made_in,
            'is_inclusive_tax' => $this->is_inclusive_tax,
            'video_type' => $this->video_type,
            'video_link' => $this->video_link,
            'status' => $this->status,
            'featured' => $this->featured,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'store_status' => $this->whenLoaded('variants')->first()->storeProductVariants->first()?->store->checkStoreStatus() ?? [],
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'attributes' => $this->getFormattedVariantAttributes(),
        ];
    }
}
