<?php

namespace App\Http\Resources\Product;

use App\Models\Review;
use App\Models\SellerFeedback;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $reviews = Review::scopeProductRatingStats($this->id);
        $stats = SellerFeedback::getSellerFeedbackStatistics($this->seller_id);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'category_id' => $this->category_id,
            'category_hierarchy_key' => $this->category_hierarchy_key,
            'brand_id' => $this->brand_id,
            'seller_id' => $this->seller_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'category' => $this->category?->slug,
            'category_parent_id' => $this->category?->parent_id,
            'brand' => $this->brand?->slug,
            'item_count_in_cart' => $this->itemCountInCart ?? 0,
            'category_name' => $this->category?->title,
            'brand_name' => $this->brand?->title,
            'seller' => $this->seller?->user->name ?? "N/A",
            'indicator' => $this->indicator,
            'favorite' => $this->favorite,
            'estimated_delivery_time' => $this->estimated_delivery_time,
            'base_prep_time' => $this->base_prep_time ?? 0,
            'ratings' => (float)($reviews['average_rating'] ?? 0),
            'rating_count' => (float)($reviews['total_reviews'] ?? 0),
            'main_image' => $this->main_image,
            'image_fit' => $this->image_fit,
            'additional_images' => $this->additional_images,
            'minimum_order_quantity' => (float) $this->minimum_order_quantity,
            'quantity_step_size' => (float) $this->quantity_step_size,
            'total_allowed_quantity' => (float) $this->total_allowed_quantity,
            'is_returnable' => (float)$this->is_returnable,
            'is_attachment_required' => (float)$this->is_attachment_required,
            'requires_otp' => (float)$this->requires_otp,
            'returnable_days' => $this->returnable_days,
            'is_cancelable' => (float)$this->is_cancelable,
            'cancelable_till' => $this->cancelable_till,
            'tags' => $this->tags,
            'custom_fields' => $this->custom_fields ?? [],
            'warranty_period' => $this->warranty_period,
            'guarantee_period' => $this->guarantee_period,
            'made_in' => $this->made_in,
            'tax_classes' => $this->taxClasses,
            'is_inclusive_tax' => $this->is_inclusive_tax,
            'video_type' => $this->video_type,
            'video_link' => $this->video_link,
            'status' => $this->status,
            'featured' => $this->featured,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'seller_ratings' => $stats,
            'store_status' => optional(
                $this->variants->first()?->storeProductVariants->first()?->store
            )?->checkStoreStatus() ?? [],
            'variants' => SellerProductVariantResource::collection($this->whenLoaded('variants')),
            'attributes' => $this->getFormattedVariantAttributes(),
            // Custom Product Sections with nested fields (including image and pivot sort order)
            'custom_product_sections' => ProductCustomSectionResource::collection(
                $this->whenLoaded('customProductSections')
            ),
        ];
    }
}
