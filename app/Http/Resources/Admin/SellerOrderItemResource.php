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
            'is_gift' => (bool)$this->orderItem?->is_gift,
            'tax_percent' => $this->orderItem?->tax_percent,
            'tax_amount' => $this->orderItem?->tax_amount,
            'tax_total' => $this->orderItem?->tax_total,
            'tax_breakdown' => $this->orderItem?->tax_breakdown ?? [],
            'is_tax_inclusive' => (bool) $this->orderItem?->is_tax_inclusive,
            'taxable_subtotal' => $this->orderItem
                ? round((float) $this->orderItem->subtotal - (float) $this->orderItem->tax_total, 2)
                : 0,
            'unit_price_with_tax' => $this->orderItem
                ? round((float) $this->orderItem->subtotal / max((float) $this->quantity, 1), 2)
                : 0,
            'subtotal' => $this->orderItem?->subtotal,
            'hsn_code' => $this->orderItem?->hsn_code,
            'supplier_tax_name' => $this->orderItem?->supplier_tax_name,
            'supplier_tax_number' => $this->orderItem?->supplier_tax_number,

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
                    'is_gift' => (bool)$this->orderItem->is_gift,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }
}
