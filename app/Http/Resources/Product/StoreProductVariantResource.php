<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreProductVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'sku' => $this->sku,
            'price' => $this->price,
            'special_price' => $this->special_price,
            'cost' => $this->cost,
            'stock' => $this->stock,
        ];
    }
}

