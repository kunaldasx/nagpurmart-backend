<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'image' => $this->image ?? '',
            'banner' => $this->banner ?? '',
            'icon' => $this->icon ?? '',
            'active_icon' => $this->active_icon ?? '',
            'background_type' => $this->background_type?->value ?? null,
            'background_color' => $this->background_color ?? '',
            'background_image' => $this->background_image ?? '',
            'font_color' => $this->font_color ?? '',
            'parent_id' => $this->parent_id,
            'commission' => $this->commission ?? 0,
            'parent_slug' => $this->parent->slug ?? null,
            'description' => $this->description,
            'status' => $this->status,
            'requires_approval' => $this->requires_approval,
            'metadata' => $this->metadata,
            'subcategory_count' => $this->children_count ?? 0,
            'product_count' => $this->products_count ?? 0,
            // Optional flag injected by controllers to indicate if this option is currently selectable
            'enabled' => (bool) data_get($this->resource, 'enabled', true),
        ];
    }
}
