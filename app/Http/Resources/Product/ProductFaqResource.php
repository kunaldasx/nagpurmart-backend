<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductFaqResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product->id,
            'product_slug' => $this->product->slug,
            'product' => [
                'id' => $this->product->id,
                'title' => $this->product->title,
                'slug' => $this->product->slug,
            ],
            'question' => $this->question,
            'answer' => $this->answer,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
