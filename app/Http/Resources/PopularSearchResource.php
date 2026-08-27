<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PopularSearchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'parent' => new CategoryResource($this->category?->parent),
        ];
    }
}