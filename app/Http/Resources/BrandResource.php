<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BrandResource extends JsonResource
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
            'logo' => $this->logo ?? '',
            'status' => $this->status,
            'scope_type' => $this->scope_type,
            'scope_id' => $this->scope_id,
            'scope_category_slug' => $this->scopeCategory->slug ?? "",
            'scope_category_title' => $this->scopeCategory->title ?? "",
            'description' => $this->description,
            'metadata' => $this->metadata,
            'total_products' => $this->products_count ?? $this->products()->count(),
            // Optional flag injected by controllers to indicate if this option is currently selectable
            'enabled' => (bool) data_get($this->resource, 'enabled', true),
        ];
    }
}

