<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

class SellerStoreProductVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store_slug' => $this->whenLoaded('store', fn () => $this->store?->slug),
            'store_name' => $this->whenLoaded('store', fn () => $this->store?->name),
            'sku' => $this->sku,
            'price' => $this->price,
            'special_price' => $this->special_price,
            'wholesale_price' => $this->wholesale_price,
            'unit_price_basis_quantity' => $this->unit_price_basis_quantity,
            'unit_price_basis_unit' => $this->unit_price_basis_unit,
            'cost' => $this->cost,
            'stock' => $this->stock,
        ];
    }
}
