<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerOrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'seller_order_id' => $this->seller_order_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'order_item_id' => $this->order_item_id,
            'quantity' => $this->quantity,
            'price' => $this->price,

            // Relationships
            'product' => $this->whenLoaded('product', function() {
                return [
                    'id' => $this->product->id,
                    'title' => $this->product->title,
                    'slug' => $this->product->slug,
                    'main_image' => $this->product->main_image,
                ];
            }),
            'variant' => $this->whenLoaded('variant', function() {
                return [
                    'id' => $this->variant->id,
                    'title' => $this->variant->title,
                    'slug' => $this->variant->slug,
                    'image' => $this->variant->image,
                ];
            }),
            'orderItem' => $this->whenLoaded('orderItem', function() {
                return [
                    'id' => $this->orderItem->id,
                    'status' => $this->orderItem->status,
                    'title' => $this->orderItem->title,
                    'variant_title' => $this->orderItem->variant_title,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
