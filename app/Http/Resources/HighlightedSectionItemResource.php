<?php

namespace App\Http\Resources;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

class HighlightedSectionItemResource extends JsonResource
{
    public function toArray($request): array
    {
        $item = $this->resolvedItem();
        return [
            'id' => $this->id, 'item_type' => $this->item_type?->value ?? $this->item_type,
            'item_id' => $this->item_id, 'title' => $this->title, 'subtitle' => $this->subtitle,
            'sort_order' => $this->sort_order,
            'image' => $this->image,
            'item' => match (true) {
                $item instanceof Product => new ProductResource($item),
                $item instanceof Category => new CategoryResource($item),
                $item instanceof Brand => new BrandResource($item),
                default => null,
            },
        ];
    }
}