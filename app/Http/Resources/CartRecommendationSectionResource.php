<?php

namespace App\Http\Resources;

use App\Http\Resources\Product\ProductResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CartRecommendationSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'heading' => $this->heading,
            'is_tabular' => (bool) $this->is_tabular,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
