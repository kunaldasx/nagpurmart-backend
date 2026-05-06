<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'scope_type' => $this->scope_type,
            'scope_id' => $this->scope_id,
            'scope_category_slug' => $this->scopeCategory->slug ?? "",
            'slug' => $this->slug,
            'custom_url' => $this->custom_url,
            'product_id' => $this->product_id ?? "",
            'product_slug' => $this->product->slug ?? "",
            'category_id' => $this->category_id ?? "",
            'category_slug' => $this->category->slug ?? "",
            'brand_id' => $this->brand_id ?? "",
            'brand_slug' => $this->brand->slug ?? "",
            'position' => $this->position,
            'visibility_status' => $this->visibility_status,
            'display_order' => $this->display_order,
            'metadata' => $this->metadata,
            'banner_image' => $this->banner_image,
        ];
    }
}
